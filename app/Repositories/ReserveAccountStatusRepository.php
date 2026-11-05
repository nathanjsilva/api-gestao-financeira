<?php

namespace App\Repositories;

use App\Models\ReserveAccountStatusEntry;
use Illuminate\Database\Eloquent\Collection;

class ReserveAccountStatusRepository
{
    public function __construct(
        protected ReserveAccountStatusEntry $model,
    ) {}

    public function buscarStatusVigente(int $contaId, string $competencia): ?ReserveAccountStatusEntry
    {
        return $this->model
            ->query()
            ->where('reserve_account_id', $contaId)
            ->where('competency', '<=', $competencia)
            ->orderByDesc('competency')
            ->first();
    }

    public function listarPorContaId(int $contaId): Collection
    {
        return $this->model
            ->query()
            ->where('reserve_account_id', $contaId)
            ->orderBy('competency')
            ->get();
    }

    public function definirStatus(int $contaId, string $competencia, bool $ativo): ReserveAccountStatusEntry
    {
        return $this->model->query()->updateOrCreate(
            ['reserve_account_id' => $contaId, 'competency' => $competencia],
            ['active' => $ativo],
        );
    }
}
