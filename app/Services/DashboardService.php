<?php

namespace App\Services;

use DomainException;
use App\Repositories\MonthlyReserveRepository;
use App\Repositories\ReserveAccountEntryRepository;
use App\Repositories\TransactionRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        protected TransactionRepository $transactionRepository,
        protected MonthlyReserveRepository $monthlyReserveRepository,
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
        $resumosPorTipo = $this->transactionRepository
            ->obterResumoMensalPorTipoEntreCompetencias($usuarioId, $competencias)
            ->groupBy('competency');

        $reservasMensais = $this->monthlyReserveRepository
            ->obterPorUsuarioIdECompetencias($usuarioId, $competencias)
            ->keyBy('competency');

        $reservasPorContas = $this->reserveAccountEntryRepository
            ->obterSaldoVigenteTotalIndexadoPorCompetencia($usuarioId, $competencias);

        $resumos = [];

        foreach ($competencias as $competencia) {
            $resumoPorTipo = $resumosPorTipo->get($competencia, collect());
            $reservaMensal = $reservasMensais->get($competencia);

            $totalEntradas = (float) ($resumoPorTipo->firstWhere('type', 'income')->total ?? 0);
            $totalGastos = (float) ($resumoPorTipo->firstWhere('type', 'expense')->total ?? 0);
            $valorRestante = $totalEntradas - $totalGastos;
            $reservaAnterior = (float) ($reservasPorContas[$competencia] ?? 0);
            $investimento = (float) ($reservaMensal->investimento ?? 0);
            $reservaAtual = $reservaAnterior + $valorRestante;
            $totalGuardado = $reservaAtual + $investimento;

            $resumos[$competencia] = [
                'competency' => $competencia,
                'total_income' => round($totalEntradas, 2),
                'total_expense' => round($totalGastos, 2),
                'remaining_amount' => round($valorRestante, 2),
                'previous_reserve' => round($reservaAnterior, 2),
                'current_reserve' => round($reservaAtual, 2),
                'investment' => round($investimento, 2),
                'total_saved' => round($totalGuardado, 2),
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
