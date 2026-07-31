<?php

namespace App\Repositories;

use App\Models\ReserveAccountEntry;
use Illuminate\Database\Eloquent\Collection;

class ReserveAccountEntryRepository
{
    public function __construct(
        protected ReserveAccountEntry $model,
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
    public function obterSaldoVigenteTotalIndexadoPorCompetencia(int $userId, array $competencias): array
    {
        $competencias = array_values(array_unique($competencias));

        if (empty($competencias)) {
            return [];
        }

        $maiorCompetencia = max($competencias);

        $entradas = $this->model
            ->query()
            ->join('reserve_accounts', 'reserve_accounts.id', '=', 'reserve_account_entries.reserve_account_id')
            ->where('reserve_accounts.user_id', $userId)
            ->where('reserve_accounts.active', true)
            ->where('reserve_account_entries.competency', '<=', $maiorCompetencia)
            ->orderBy('reserve_account_entries.competency')
            ->get([
                'reserve_account_entries.reserve_account_id',
                'reserve_account_entries.competency',
                'reserve_account_entries.balance',
            ])
            ->groupBy('reserve_account_id');

        $totais = [];

        foreach ($competencias as $competencia) {
            $total = 0.0;

            foreach ($entradas as $entradasDaConta) {
                $vigente = $entradasDaConta
                    ->filter(fn ($entrada): bool => $entrada->competency <= $competencia)
                    ->last();

                $total += (float) ($vigente->balance ?? 0);
            }

            $totais[$competencia] = round($total, 2);
        }

        return $totais;
    }
}
