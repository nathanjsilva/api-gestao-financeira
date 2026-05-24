<?php

namespace App\Services;

use App\Repositories\TransactionRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class FinancialAnalyticsService
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected TransactionRepository $transactionRepository,
        protected MonthlyComparisonService $monthlyComparisonService,
        protected CategoryAnalyticsService $categoryAnalyticsService,
    ) {}

    public function obterPainelAnalitico(int $usuarioId, string $competencia, int $meses = 6): array
    {
        return Cache::remember(
            "dashboard:analytics:user:{$usuarioId}:{$competencia}:{$meses}",
            now()->addMinutes(5),
            fn (): array => $this->montarPainel($usuarioId, $competencia, $meses)
        );
    }

    protected function montarPainel(int $usuarioId, string $competencia, int $meses): array
    {
        $competencias = $this->gerarCompetencias($competencia, $meses);
        $competenciaAnterior = Carbon::createFromFormat('Y-m', $competencia)->subMonth()->format('Y-m');
        $competenciasComAnterior = array_values(array_unique([...$competencias, $competenciaAnterior]));

        $evolucao = $this->dashboardService->obterEvolucaoMensal(
            $usuarioId,
            $competenciasComAnterior[0],
            end($competenciasComAnterior),
        )['months'];

        $mesesIndexados = collect($evolucao)->keyBy('competency');
        $mesAtual = $mesesIndexados->get($competencia);
        $mesAnterior = $mesesIndexados->get($competenciaAnterior) ?? $this->resumoVazio($competenciaAnterior);
        $mesesDoPeriodo = collect($competencias)
            ->map(fn (string $item): array => $mesesIndexados->get($item) ?? $this->resumoVazio($item))
            ->values();

        $gastosAtuais = $this->transactionRepository->obterTotaisDeGastosAgrupadosPorCategoria($usuarioId, $competencia);
        $gastosAnteriores = $this->transactionRepository->obterTotaisDeGastosAgrupadosPorCategoria($usuarioId, $competenciaAnterior);
        $ranking = $this->categoryAnalyticsService->montarRanking($gastosAtuais, $gastosAnteriores);
        $comparacao = $this->monthlyComparisonService->comparar($mesAtual, $mesAnterior);
        $kpis = $this->montarKpis($mesAtual, $mesesDoPeriodo->all());

        return [
            'filters' => [
                'competency' => $competencia,
                'previous_competency' => $competenciaAnterior,
                'months' => $meses,
            ],
            'overview' => $mesAtual,
            'comparison' => $comparacao,
            'kpis' => $kpis,
            'categories' => [
                'ranking' => $ranking,
                'highest_expense' => $ranking[0] ?? null,
                'lowest_expense' => ! empty($ranking) ? $ranking[array_key_last($ranking)] : null,
                'top_growth' => $this->categoryAnalyticsService->categoriasQueMaisCresceram($ranking),
            ],
            'evolution' => $mesesDoPeriodo->all(),
            'reserve_evolution' => $this->montarEvolucaoReserva($mesesDoPeriodo->all()),
            'cash_flow' => $this->montarFluxoCaixa($mesesDoPeriodo->all()),
            'heatmap' => $this->montarHeatmapCategorias($ranking),
            'insights' => $this->montarInsights($mesAtual, $comparacao, $ranking, $kpis),
        ];
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

    protected function montarKpis(array $mesAtual, array $meses): array
    {
        $totalMeses = max(count($meses), 1);
        $melhorMes = collect($meses)->sortByDesc('remaining_amount')->first();
        $piorMes = collect($meses)->sortBy('remaining_amount')->first();

        return [
            'average_monthly_expense' => round(collect($meses)->sum('total_expense') / $totalMeses, 2),
            'average_monthly_income' => round(collect($meses)->sum('total_income') / $totalMeses, 2),
            'average_remaining' => round(collect($meses)->sum('remaining_amount') / $totalMeses, 2),
            'best_month' => $melhorMes,
            'worst_month' => $piorMes,
            'income_commitment_rate' => $this->monthlyComparisonService->calcularRendaComprometida(
                (float) $mesAtual['total_expense'],
                (float) $mesAtual['total_income'],
            ),
            'savings_rate' => $this->monthlyComparisonService->calcularTaxaEconomia(
                (float) $mesAtual['remaining_amount'],
                (float) $mesAtual['total_income'],
            ),
            'projected_closing' => $mesAtual['remaining_amount'],
        ];
    }

    protected function montarEvolucaoReserva(array $meses): array
    {
        return collect($meses)
            ->map(fn (array $mes): array => [
                'competency' => $mes['competency'],
                'previous_reserve' => $mes['previous_reserve'],
                'current_reserve' => $mes['current_reserve'],
                'investment' => $mes['investment'],
                'total_saved' => $mes['total_saved'],
            ])
            ->values()
            ->all();
    }

    protected function montarFluxoCaixa(array $meses): array
    {
        return collect($meses)
            ->map(fn (array $mes): array => [
                'competency' => $mes['competency'],
                'income' => $mes['total_income'],
                'expense' => $mes['total_expense'],
                'remaining' => $mes['remaining_amount'],
            ])
            ->values()
            ->all();
    }

    protected function montarHeatmapCategorias(array $ranking): array
    {
        return collect($ranking)
            ->map(fn (array $item): array => [
                'label' => $item['category_name'],
                'value' => $item['percentage_of_expenses'],
                'tone' => match (true) {
                    $item['percentage_of_expenses'] >= 40 => 'critical',
                    $item['percentage_of_expenses'] >= 25 => 'warning',
                    $item['percentage_of_expenses'] >= 12 => 'attention',
                    default => 'safe',
                },
            ])
            ->values()
            ->all();
    }

    protected function montarInsights(array $mesAtual, array $comparacao, array $ranking, array $kpis): array
    {
        $insights = [];

        $insights[] = [
            'type' => $comparacao['expense_difference'] > 0 ? 'warning' : 'success',
            'title' => $comparacao['expense_difference'] > 0 ? 'Gastos em alta' : 'Gastos controlados',
            'description' => $comparacao['expense_difference'] > 0
                ? "Voce gastou {$comparacao['expense_percentage']}% a mais que no mes anterior."
                : "Voce reduziu seus gastos em {$comparacao['expense_percentage']}% versus o mes anterior.",
        ];

        $insights[] = [
            'type' => $comparacao['reserve_difference'] >= 0 ? 'success' : 'danger',
            'title' => $comparacao['reserve_difference'] >= 0 ? 'Reserva crescendo' : 'Reserva caiu',
            'description' => $comparacao['reserve_difference'] >= 0
                ? 'Sua reserva financeira aumentou no comparativo mensal.'
                : 'Sua reserva financeira reduziu. Vale revisar gastos ou investimentos do mes.',
        ];

        if (! empty($ranking)) {
            $principalCategoria = $ranking[0];
            $insights[] = [
                'type' => $principalCategoria['percentage_of_expenses'] >= 40 ? 'warning' : 'info',
                'title' => 'Categoria dominante',
                'description' => "{$principalCategoria['category_name']} representa {$principalCategoria['percentage_of_expenses']}% dos gastos do mes.",
            ];
        }

        if ($kpis['savings_rate'] < 0) {
            $insights[] = [
                'type' => 'danger',
                'title' => 'Fechamento negativo',
                'description' => 'Seu saldo do mes esta negativo. O dashboard indica consumo acima das entradas.',
            ];
        } elseif ($kpis['savings_rate'] >= 20) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Boa taxa de economia',
                'description' => 'Voce esta guardando uma parcela relevante da renda deste mes.',
            ];
        }

        return $insights;
    }

    protected function resumoVazio(string $competencia): array
    {
        return [
            'competency' => $competencia,
            'total_income' => 0,
            'total_expense' => 0,
            'remaining_amount' => 0,
            'previous_reserve' => 0,
            'current_reserve' => 0,
            'investment' => 0,
            'total_saved' => 0,
        ];
    }
}

