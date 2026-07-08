<?php

namespace App\Services;

use App\Events\DadosFinanceirosAlterados;
use App\Models\MonthlyReserve;
use App\Models\MonthlyReserveEntry;
use App\Repositories\MonthlyReserveEntryRepository;
use App\Repositories\MonthlyReserveRepository;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class MonthlyReserveService
{
    public function __construct(
        protected MonthlyReserveRepository $monthlyReserveRepository,
        protected MonthlyReserveEntryRepository $monthlyReserveEntryRepository,
        protected DashboardService $dashboardService,
    ) {}

    public function listar(int $usuarioId): Collection
    {
        $reservas = $this->monthlyReserveRepository->listarPorUsuarioId($usuarioId);

        $this->anexarSaldos($usuarioId, $reservas);

        return $reservas;
    }

    public function buscarPorCompetencia(int $usuarioId, string $competencia): ?MonthlyReserve
    {
        return $this->monthlyReserveRepository->buscarPorUsuarioIdECompetencia($usuarioId, $competencia);
    }

    public function buscarPorId(int $id, int $usuarioId): ?MonthlyReserve
    {
        $reservaMensal = $this->monthlyReserveRepository->buscarPorIdEUsuarioId($id, $usuarioId);

        if ($reservaMensal !== null) {
            $this->anexarSaldos($usuarioId, [$reservaMensal]);
        }

        return $reservaMensal;
    }

    public function criar(int $usuarioId, array $dados): MonthlyReserve
    {
        $reservaExistente = $this->buscarPorCompetencia($usuarioId, $dados['competency']);

        if ($reservaExistente !== null) {
            throw new DomainException('Ja existe uma reserva mensal cadastrada para esta competencia.');
        }

        $reserva = $this->monthlyReserveRepository->criar([
            'user_id' => $usuarioId,
            'competency' => $dados['competency'],
            'reserva_anterior' => $dados['reserva_anterior'],
            'investimento' => $dados['investimento'],
            'observations' => $dados['observations'] ?? null,
        ]);

        event(new DadosFinanceirosAlterados($usuarioId));

        $this->anexarSaldos($usuarioId, [$reserva]);

        return $reserva;
    }

    public function atualizar(int $id, int $usuarioId, array $dados): MonthlyReserve
    {
        $reservaMensal = $this->buscarPorIdOuFalhar($id, $usuarioId);

        if (isset($dados['competency']) && $dados['competency'] !== $reservaMensal->competency) {
            $reservaExistente = $this->buscarPorCompetencia($usuarioId, $dados['competency']);

            if ($reservaExistente !== null) {
                throw new DomainException('Ja existe uma reserva mensal cadastrada para a competencia informada.');
            }
        }

        $this->monthlyReserveRepository->atualizar($reservaMensal, $dados);

        event(new DadosFinanceirosAlterados($usuarioId));

        $reservaMensal->refresh();

        $this->anexarSaldos($usuarioId, [$reservaMensal]);

        return $reservaMensal;
    }

    public function excluir(int $id, int $usuarioId): void
    {
        $reservaMensal = $this->buscarPorIdOuFalhar($id, $usuarioId);

        $this->monthlyReserveRepository->excluir($reservaMensal);

        event(new DadosFinanceirosAlterados($usuarioId));
    }

    public function sugerirReservaAnterior(int $usuarioId, string $competencia): float
    {
        $competenciaAnterior = Carbon::createFromFormat('Y-m', $competencia)
            ->subMonth()
            ->format('Y-m');

        $reservaDoMesAnterior = $this->buscarPorCompetencia($usuarioId, $competenciaAnterior);

        if ($reservaDoMesAnterior === null) {
            return 0.0;
        }

        $saldoAnterior = $this->dashboardService->obterSaldoDaCompetencia($usuarioId, $competenciaAnterior);

        return (float) $saldoAnterior['total_saved'];
    }

    public function listarLancamentos(int $reservaId, int $usuarioId): Collection
    {
        $reservaMensal = $this->buscarPorIdOuFalhar($reservaId, $usuarioId);

        return $this->monthlyReserveEntryRepository->listarPorReservaId($reservaMensal->id);
    }

    public function criarLancamento(int $reservaId, int $usuarioId, array $dados): MonthlyReserveEntry
    {
        $reservaMensal = $this->buscarPorIdOuFalhar($reservaId, $usuarioId);

        $lancamento = $this->monthlyReserveEntryRepository->criar($reservaMensal->id, $dados);

        $this->recalcularInvestimento($reservaMensal);

        event(new DadosFinanceirosAlterados($usuarioId));

        return $lancamento;
    }

    public function atualizarLancamento(int $reservaId, int $lancamentoId, int $usuarioId, array $dados): MonthlyReserveEntry
    {
        $reservaMensal = $this->buscarPorIdOuFalhar($reservaId, $usuarioId);
        $lancamento = $this->buscarLancamentoOuFalhar($lancamentoId, $reservaMensal->id);

        $this->monthlyReserveEntryRepository->atualizar($lancamento, $dados);

        $this->recalcularInvestimento($reservaMensal);

        event(new DadosFinanceirosAlterados($usuarioId));

        return $lancamento->refresh();
    }

    public function excluirLancamento(int $reservaId, int $lancamentoId, int $usuarioId): void
    {
        $reservaMensal = $this->buscarPorIdOuFalhar($reservaId, $usuarioId);
        $lancamento = $this->buscarLancamentoOuFalhar($lancamentoId, $reservaMensal->id);

        $this->monthlyReserveEntryRepository->excluir($lancamento);

        $this->recalcularInvestimento($reservaMensal);

        event(new DadosFinanceirosAlterados($usuarioId));
    }

    /**
     * @param  iterable<MonthlyReserve>  $reservas
     */
    protected function anexarSaldos(int $usuarioId, iterable $reservas): void
    {
        $competencias = collect($reservas)->pluck('competency')->all();

        if (empty($competencias)) {
            return;
        }

        $saldos = $this->dashboardService->obterSaldosIndexadosPorCompetencia($usuarioId, $competencias);

        foreach ($reservas as $reserva) {
            $reserva->setAttribute('saldo_calculado', $saldos[$reserva->competency] ?? null);
        }
    }

    protected function recalcularInvestimento(MonthlyReserve $reservaMensal): void
    {
        $totalLancamentos = $this->monthlyReserveEntryRepository->somarPorReservaId($reservaMensal->id);

        $this->monthlyReserveRepository->atualizar($reservaMensal, [
            'investimento' => $totalLancamentos,
        ]);
    }

    protected function buscarLancamentoOuFalhar(int $lancamentoId, int $reservaId): MonthlyReserveEntry
    {
        $lancamento = $this->monthlyReserveEntryRepository->buscarPorIdEReservaId($lancamentoId, $reservaId);

        if ($lancamento !== null) {
            return $lancamento;
        }

        throw new DomainException('Lancamento nao encontrado para esta reserva.');
    }

    protected function buscarPorIdOuFalhar(int $id, int $usuarioId): MonthlyReserve
    {
        $reservaMensal = $this->monthlyReserveRepository->buscarPorIdEUsuarioId($id, $usuarioId);

        if ($reservaMensal !== null) {
            return $reservaMensal;
        }

        throw new DomainException('Reserva mensal nao encontrada para este usuario.');
    }
}
