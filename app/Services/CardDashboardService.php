<?php

namespace App\Services;

use App\Repositories\CardInstallmentRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CardDashboardService
{
    public function __construct(
        protected CardInstallmentRepository $cardInstallmentRepository,
        protected CardAnalyticsService $cardAnalyticsService,
    ) {}

    public function obterPainelAnalitico(int $usuarioId, string $competencia, int $meses = 6): array
    {
        return Cache::remember(
            $this->chaveDeCache($usuarioId, $competencia, $meses),
            now()->addMinutes(5),
            fn (): array => $this->montarPainel($usuarioId, $competencia, $meses)
        );
    }

    public function obterResumoMensal(int $usuarioId, string $competencia): array
    {
        $ano = substr($competencia, 0, 4);

        return [
            'competency' => $competencia,
            'total_month' => round($this->cardInstallmentRepository->obterTotalPorCompetencia($usuarioId, $competencia), 2),
            'total_year' => round($this->cardInstallmentRepository->obterTotalPorAno($usuarioId, $ano), 2),
            'by_card' => $this->cardInstallmentRepository->obterTotalAgrupadoPorCartao($usuarioId, $competencia),
            'by_person' => $this->cardInstallmentRepository->obterTotalAgrupadoPorPessoa($usuarioId, $competencia),
            'by_category' => $this->cardInstallmentRepository->obterTotalAgrupadoPorCategoria($usuarioId, $competencia),
            'committed_future' => round($this->cardInstallmentRepository->obterTotalComprometidoFuturo($usuarioId, $competencia), 2),
            'outstanding_balance' => round($this->cardInstallmentRepository->obterSaldoDevedorParcelamentosEmAberto($usuarioId, $competencia), 2),
        ];
    }

    private function chaveDeCache(int $usuarioId, string $competencia, int $meses): string
    {
        $versao = Cache::get("cards:user:{$usuarioId}:version", 1);

        return "cards:analytics:user:{$usuarioId}:{$competencia}:{$meses}:v{$versao}";
    }

    protected function montarPainel(int $usuarioId, string $competencia, int $meses): array
    {
        $competencias = $this->gerarCompetencias($competencia, $meses);
        $competenciaAnterior = Carbon::createFromFormat('Y-m', $competencia)->subMonth()->format('Y-m');
        $ano = substr($competencia, 0, 4);

        $totalMes = $this->cardInstallmentRepository->obterTotalPorCompetencia($usuarioId, $competencia);
        $totalAno = $this->cardInstallmentRepository->obterTotalPorAno($usuarioId, $ano);

        $porCartao = $this->cardInstallmentRepository->obterTotalAgrupadoPorCartao($usuarioId, $competencia);
        $porPessoa = $this->cardInstallmentRepository->obterTotalAgrupadoPorPessoa($usuarioId, $competencia);

        $gastosAtuaisPorCategoria = $this->cardInstallmentRepository->obterTotalAgrupadoPorCategoria($usuarioId, $competencia);
        $gastosAnterioresPorCategoria = $this->cardInstallmentRepository->obterTotalAgrupadoPorCategoria($usuarioId, $competenciaAnterior);
        $rankingCategorias = $this->cardAnalyticsService->montarRankingCategorias($gastosAtuaisPorCategoria, $gastosAnterioresPorCategoria);

        $porTipoPagamento = $this->cardInstallmentRepository->obterTotalAgrupadoPorTipoPagamento($usuarioId, $competencia);
        $comprometidoFuturo = $this->cardInstallmentRepository->obterTotalComprometidoFuturo($usuarioId, $competencia);
        $saldoDevedor = $this->cardInstallmentRepository->obterSaldoDevedorParcelamentosEmAberto($usuarioId, $competencia);

        $evolucaoRaw = $this->cardInstallmentRepository->obterEvolucaoMensal($usuarioId, $competencias)->keyBy('competency');
        $evolucao = collect($competencias)
            ->map(fn (string $item): array => [
                'competency' => $item,
                'total' => round((float) ($evolucaoRaw->get($item)->total ?? 0), 2),
            ])
            ->values()
            ->all();

        $mediaHistorica = $this->cardInstallmentRepository->obterMediaHistoricaMensal(
            $usuarioId,
            array_slice($competencias, 0, -1),
        );

        $topCategorias = collect($rankingCategorias)->take(5)->values()->all();
        $topCartoes = $this->cardInstallmentRepository->obterCartoesComMaisVolume($usuarioId, $competencia, 5);

        return [
            'filters' => [
                'competency' => $competencia,
                'previous_competency' => $competenciaAnterior,
                'months' => $meses,
            ],
            'overview' => [
                'total_month' => round($totalMes, 2),
                'total_year' => round($totalAno, 2),
            ],
            'by_card' => $porCartao,
            'by_person' => $porPessoa,
            'by_category' => [
                'ranking' => $rankingCategorias,
                'top_growth' => $this->cardAnalyticsService->categoriasQueMaisCresceram($rankingCategorias),
                'concentration' => $this->cardAnalyticsService->calcularConcentracao($rankingCategorias),
            ],
            'evolution' => $evolucao,
            'payment_type_breakdown' => $porTipoPagamento,
            'committed_future' => round($comprometidoFuturo, 2),
            'outstanding_balance' => round($saldoDevedor, 2),
            'top_categories' => $topCategorias,
            'top_cards' => $topCartoes,
            'insights' => $this->montarInsights($totalMes, $mediaHistorica, $rankingCategorias, $comprometidoFuturo),
        ];
    }

    protected function montarInsights(
        float $totalMes,
        float $mediaHistorica,
        array $rankingCategorias,
        float $comprometidoFuturo,
    ): array {
        $insights = [];

        $atipico = $this->cardAnalyticsService->detectarGastoAtipico($totalMes, $mediaHistorica);

        if ($atipico['is_atipico']) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Gasto acima da média',
                'description' => "Os gastos deste mês no cartão estão {$atipico['desvio_percentual']}% acima da média histórica.",
            ];
        }

        if (! empty($rankingCategorias)) {
            $principal = $rankingCategorias[0];

            if ($principal['percentage_of_expenses'] >= 35) {
                $insights[] = [
                    'type' => 'info',
                    'title' => 'Categoria dominante',
                    'description' => "{$principal['category_name']} representa {$principal['percentage_of_expenses']}% dos gastos de cartão neste mês.",
                ];
            }

            if ($principal['growth_percentage'] > 20) {
                $insights[] = [
                    'type' => 'warning',
                    'title' => 'Categoria em alta',
                    'description' => "Os gastos com {$principal['category_name']} aumentaram {$principal['growth_percentage']}% em relação ao mês anterior.",
                ];
            }
        }

        if ($comprometidoFuturo > 0) {
            $valorFormatado = 'R$ '.number_format($comprometidoFuturo, 2, ',', '.');

            $insights[] = [
                'type' => 'info',
                'title' => 'Parcelas futuras',
                'description' => "Existem {$valorFormatado} comprometidos com parcelas nos próximos meses.",
            ];
        }

        return $insights;
    }

    /**
     * @return list<string>
     */
    protected function gerarCompetencias(string $competenciaFinal, int $meses): array
    {
        $inicio = Carbon::createFromFormat('Y-m', $competenciaFinal)->subMonths($meses - 1)->startOfMonth();
        $fim = Carbon::createFromFormat('Y-m', $competenciaFinal)->startOfMonth();
        $competencias = [];

        while ($inicio->lessThanOrEqualTo($fim)) {
            $competencias[] = $inicio->format('Y-m');
            $inicio->addMonth();
        }

        return $competencias;
    }
}
