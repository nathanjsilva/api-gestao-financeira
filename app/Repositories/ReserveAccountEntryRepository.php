<?php

namespace App\Repositories;

use App\Models\ReserveAccount;
use App\Models\ReserveAccountEntry;
use App\Models\ReserveAccountStatusEntry;
use Illuminate\Database\Eloquent\Collection;

class ReserveAccountEntryRepository
{
    public function __construct(
        protected ReserveAccountEntry $model,
        protected ReserveAccount $reserveAccountModel,
        protected ReserveAccountStatusEntry $statusModel,
    ) {}

    public function listarPorContaId(int $contaId): Collection
    {
        return $this->model
            ->query()
            ->where('reserve_account_id', $contaId)
            ->orderByDesc('competency')
            ->get();
    }

    public function buscarPorContaIdECompetencia(int $contaId, string $competencia): ?ReserveAccountEntry
    {
        return $this->model
            ->query()
            ->where('reserve_account_id', $contaId)
            ->where('competency', $competencia)
            ->first();
    }

    /**
     * Retorna a entry mais recente da conta com competencia menor ou igual
     * a informada — e a base da heranca automatica entre meses.
     */
    public function buscarSaldoVigente(int $contaId, string $competencia): ?ReserveAccountEntry
    {
        return $this->model
            ->query()
            ->where('reserve_account_id', $contaId)
            ->where('competency', '<=', $competencia)
            ->orderByDesc('competency')
            ->first();
    }

    public function upsert(int $contaId, string $competencia, array $dados): ReserveAccountEntry
    {
        return $this->model->query()->updateOrCreate(
            ['reserve_account_id' => $contaId, 'competency' => $competencia],
            ['balance' => $dados['balance'], 'note' => $dados['note'] ?? null],
        );
    }

    public function excluir(ReserveAccountEntry $entry): ?bool
    {
        return $entry->delete();
    }

    /**
     * Soma, por competencia, o saldo vigente de todas as contas ativas do
     * usuario — usado pelo DashboardService no lugar do antigo campo unico
     * "reserva_anterior".
     *
     * @param  list<string>  $competencias
     * @return array<string, float>
     */
    public function obterSaldosVigentesIndexadosPorTipoECompetencia(int $userId, array $competencias): array
    {
        $competencias = array_values(array_unique($competencias));

        if (empty($competencias)) {
            return [];
        }

        $maiorCompetencia = max($competencias);

        $contas = $this->reserveAccountModel
            ->query()
            ->where('user_id', $userId)
            ->get(['id', 'type', 'active', 'archive_status_needs_review'])
            ->keyBy('id');

        if ($contas->isEmpty()) {
            return array_fill_keys($competencias, [
                'reserve' => 0.0,
                'investment' => 0.0,
                'has_unresolved_archived_items' => false,
            ]);
        }

        $ids = $contas->keys()->all();
        $entradas = $this->model
            ->query()
            ->whereIn('reserve_account_id', $ids)
            ->where('competency', '<=', $maiorCompetencia)
            ->orderBy('competency')
            ->get(['reserve_account_id', 'competency', 'balance'])
            ->groupBy('reserve_account_id');

        $statusEntries = $this->statusModel
            ->query()
            ->whereIn('reserve_account_id', $ids)
            ->where('competency', '<=', $maiorCompetencia)
            ->orderBy('competency')
            ->get(['reserve_account_id', 'competency', 'active'])
            ->groupBy('reserve_account_id');

        $totais = [];

        foreach ($competencias as $competencia) {
            $totais[$competencia] = [
                'reserve' => 0.0,
                'investment' => 0.0,
                'has_unresolved_archived_items' => false,
            ];

            foreach ($contas as $contaId => $conta) {
                $entradasDaConta = $entradas->get($contaId, collect());
                $vigente = $entradasDaConta
                    ->filter(fn ($entrada): bool => $entrada->competency <= $competencia)
                    ->last();

                $statusVigente = $statusEntries
                    ->get($contaId, collect())
                    ->filter(fn ($status): bool => $status->competency <= $competencia)
                    ->last();

                $statusAtivo = $statusVigente?->active;

                if ($statusAtivo === null && $conta->archive_status_needs_review) {
                    if ($vigente !== null) {
                        $totais[$competencia]['has_unresolved_archived_items'] = true;
                    }

                    continue;
                }

                if ($statusAtivo === false || $vigente === null) {
                    continue;
                }

                $tipo = $conta->type === 'investment' ? 'investment' : 'reserve';
                $totais[$competencia][$tipo] += (float) $vigente->balance;
            }

            $totais[$competencia]['reserve'] = round($totais[$competencia]['reserve'], 2);
            $totais[$competencia]['investment'] = round($totais[$competencia]['investment'], 2);
        }

        return $totais;
    }

    public function obterSaldoVigenteTotalIndexadoPorCompetencia(int $userId, array $competencias): array
    {
        $saldos = $this->obterSaldosVigentesIndexadosPorTipoECompetencia($userId, $competencias);

        return collect($saldos)
            ->map(fn (array $saldo): float => round($saldo['reserve'] + $saldo['investment'], 2))
            ->all();
    }
}
