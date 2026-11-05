<?php

namespace App\Services;

use App\Repositories\ReserveAccountEntryRepository;
use App\Repositories\TransactionRepository;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        protected TransactionRepository $transactionRepository,
        protected ReserveAccountEntryRepository $reserveAccountEntryRepository,
    ) {}

    public function obterResumoMensal(int $usuarioId, string $competencia): array
    {
        $gastosPorCategoria = $this->transactionRepository
            ->obterTotaisDeGastosAgrupadosPorCategoria($usuarioId, $competencia);

        $resumo = $this->obterResumosBaseIndexados($usuarioId, [$competencia])[$competencia];

        $categoriaMaiorGasto = $gastosPorCategoria->first();
        $categoriaMenorGasto = $gastosPorCategoria->last();

        return array_merge($resumo, [
            'highest_expense_category' => $categoriaMaiorGasto?->category?->name,
            'lowest_expense_category' => $categoriaMenorGasto?->category?->name,
            'expenses_by_category' => $gastosPorCategoria,
        ]);
    }

    public function obterComparativoDeCategorias(
        int $usuarioId,
        string $competenciaAtual,
        string $competenciaAnterior,
    ): array {
        $gastos = $this->transactionRepository->obterTotaisDeGastosPorCategoriaEntreCompetencias(
            $usuarioId,
            $competenciaAtual,
            $competenciaAnterior,
        );

        $categorias = $gastos
            ->groupBy('category_id')
            ->map(function (Collection $itens) use ($competenciaAtual, $competenciaAnterior): array {
                $itemAtual = $itens->firstWhere('competency', $competenciaAtual);
                $itemAnterior = $itens->firstWhere('competency', $competenciaAnterior);

                $totalAtual = (float) ($itemAtual->total ?? 0);
                $totalAnterior = (float) ($itemAnterior->total ?? 0);

                return [
                    'category_id' => $itens->first()->category_id,
                    'category_name' => $itens->first()->category?->name,
                    'current_total' => round($totalAtual, 2),
                    'previous_total' => round($totalAnterior, 2),
                    'difference' => round($totalAtual - $totalAnterior, 2),
                ];
            })
            ->sortByDesc('current_total')
            ->values();

        return [
            'current_competency' => $competenciaAtual,
            'previous_competency' => $competenciaAnterior,
            'categories' => $categorias,
        ];
    }

    public function obterEvolucaoMensal(
        int $usuarioId,
        string $competenciaInicial,
        string $competenciaFinal,
    ): array {
        $competencias = $this->gerarCompetenciasEntrePeriodos($competenciaInicial, $competenciaFinal);
        $resumos = $this->obterResumosBaseIndexados($usuarioId, $competencias);
        $evolucao = collect($competencias)->map(
            fn (string $competencia): array => $resumos[$competencia]
        );

        return [
            'start_competency' => $competenciaInicial,
            'end_competency' => $competenciaFinal,
            'months' => $evolucao,
        ];
    }

    public function obterComparacaoEntreMeses(
        int $usuarioId,
        string $primeiraCompetencia,
        string $segundaCompetencia,
    ): array {
        $resumos = $this->obterResumosBaseIndexados($usuarioId, [
            $primeiraCompetencia,
            $segundaCompetencia,
        ]);
        $primeiroMes = $resumos[$primeiraCompetencia];
        $segundoMes = $resumos[$segundaCompetencia];

        return [
            'first_month' => $primeiroMes,
            'second_month' => $segundoMes,
            'difference' => [
                'total_income' => round($segundoMes['total_income'] - $primeiroMes['total_income'], 2),
                'total_expense' => round($segundoMes['total_expense'] - $primeiroMes['total_expense'], 2),
                'remaining_amount' => round($segundoMes['remaining_amount'] - $primeiroMes['remaining_amount'], 2),
                'current_reserve' => round($segundoMes['current_reserve'] - $primeiroMes['current_reserve'], 2),
                'total_saved' => round($segundoMes['total_saved'] - $primeiroMes['total_saved'], 2),
            ],
        ];
    }

    public function obterSaldoDaCompetencia(int $usuarioId, string $competencia): array
    {
        return $this->obterResumosBaseIndexados($usuarioId, [$competencia])[$competencia];
    }

    /**
     * @param  list<string>  $competencias
     * @return array<string, array<string, float|string>>
     */
    public function obterSaldosIndexadosPorCompetencia(int $usuarioId, array $competencias): array
    {
        return $this->obterResumosBaseIndexados($usuarioId, $competencias);
    }

    /**
     * @param  list<string>  $competencias
     * @return array<string, array<string, float|string>>
     */
    protected function obterResumosBaseIndexados(int $usuarioId, array $competencias): array
    {
        $competencias = array_values(array_unique($competencias));
        $competenciasComAnterior = collect($competencias)
            ->flatMap(fn (string $competencia): array => [
                $competencia,
                Carbon::createFromFormat('Y-m', $competencia)->subMonth()->format('Y-m'),
            ])
            ->unique()
            ->values()
            ->all();
        $resumosPorTipoEStatus = $this->transactionRepository
            ->obterResumoMensalPorTipoEStatusEntreCompetencias($usuarioId, $competencias)
            ->groupBy('competency');
        $saldosPatrimoniais = $this->reserveAccountEntryRepository
            ->obterSaldosVigentesIndexadosPorTipoECompetencia($usuarioId, $competenciasComAnterior);

        $resumos = [];

        foreach ($competencias as $competencia) {
            $resumoPorTipoEStatus = $resumosPorTipoEStatus->get($competencia, collect());
            $saldosAtuais = $saldosPatrimoniais[$competencia] ?? [
                'reserve' => 0.0,
                'investment' => 0.0,
                'has_unresolved_archived_items' => false,
            ];
            $competenciaAnterior = Carbon::createFromFormat('Y-m', $competencia)->subMonth()->format('Y-m');
            $saldosAnteriores = $saldosPatrimoniais[$competenciaAnterior] ?? [
                'reserve' => 0.0,
                'investment' => 0.0,
            ];

            $totalEntradas = (float) $resumoPorTipoEStatus
                ->where('type', 'income')->where('status', 'paid')->sum('total');
            $totalGastos = (float) $resumoPorTipoEStatus
                ->where('type', 'expense')->where('status', 'paid')->sum('total');
            $entradasPendentes = (float) $resumoPorTipoEStatus
                ->where('type', 'income')->where('status', 'pending')->sum('total');
            $gastosPendentes = (float) $resumoPorTipoEStatus
                ->where('type', 'expense')->where('status', 'pending')->sum('total');
            $valorRestante = $totalEntradas - $totalGastos;
            $resultadoProjetado = $totalEntradas + $entradasPendentes - $totalGastos - $gastosPendentes;
            $reservaAtual = (float) $saldosAtuais['reserve'];
            $investimento = (float) $saldosAtuais['investment'];
            $totalGuardado = $reservaAtual + $investimento;
            $totalGuardadoAnterior = (float) $saldosAnteriores['reserve'] + (float) $saldosAnteriores['investment'];

            $resumos[$competencia] = [
                'competency' => $competencia,
                'total_income' => round($totalEntradas, 2),
                'total_expense' => round($totalGastos, 2),
                'remaining_amount' => round($valorRestante, 2),
                'realized_income' => round($totalEntradas, 2),
                'realized_expense' => round($totalGastos, 2),
                'realized_result' => round($valorRestante, 2),
                'pending_income' => round($entradasPendentes, 2),
                'pending_expense' => round($gastosPendentes, 2),
                'projected_result' => round($resultadoProjetado, 2),
                'previous_reserve' => round((float) $saldosAnteriores['reserve'], 2),
                'current_reserve' => round($reservaAtual, 2),
                'investment' => round($investimento, 2),
                'total_saved' => round($totalGuardado, 2),
                'reserve_total' => round($reservaAtual, 2),
                'investment_total' => round($investimento, 2),
                'previous_total_saved' => round($totalGuardadoAnterior, 2),
                'asset_variation' => round($totalGuardado - $totalGuardadoAnterior, 2),
                'reserve_variation' => round($reservaAtual - (float) $saldosAnteriores['reserve'], 2),
                'investment_variation' => round($investimento - (float) $saldosAnteriores['investment'], 2),
                'has_unresolved_archived_items' => (bool) $saldosAtuais['has_unresolved_archived_items'],
            ];
        }

        return $resumos;
    }

    /**
     * @return list<string>
     */
    protected function gerarCompetenciasEntrePeriodos(
        string $competenciaInicial,
        string $competenciaFinal,
    ): array {
        if ($competenciaInicial > $competenciaFinal) {
            throw new DomainException('A competencia inicial nao pode ser maior que a competencia final.');
        }

        $inicio = Carbon::createFromFormat('Y-m', $competenciaInicial)->startOfMonth();
        $fim = Carbon::createFromFormat('Y-m', $competenciaFinal)->startOfMonth();
        $competencias = [];

        while ($inicio->lessThanOrEqualTo($fim)) {
            $competencias[] = $inicio->format('Y-m');
            $inicio->addMonth();
        }

        return $competencias;
    }
}
