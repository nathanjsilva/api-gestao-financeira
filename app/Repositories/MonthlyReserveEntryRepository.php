<?php

namespace App\Repositories;

use App\Models\MonthlyReserveEntry;
use Illuminate\Database\Eloquent\Collection;

class MonthlyReserveEntryRepository
{
    public function __construct(
        protected MonthlyReserveEntry $model,
    ) {}

    public function listarPorReservaId(int $reservaId): Collection
    {
        return $this->model
            ->query()
            ->where('monthly_reserve_id', $reservaId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function buscarPorIdEReservaId(int $id, int $reservaId): ?MonthlyReserveEntry
    {
        return $this->model
            ->query()
            ->where('id', $id)
            ->where('monthly_reserve_id', $reservaId)
            ->first();
    }

    public function criar(int $reservaId, array $dados): MonthlyReserveEntry
    {
        return $this->model->query()->create([
            'monthly_reserve_id' => $reservaId,
            'description' => $dados['description'],
            'amount' => $dados['amount'],
        ]);
    }

    public function atualizar(MonthlyReserveEntry $lancamento, array $dados): bool
    {
        return $lancamento->update($dados);
    }

    public function excluir(MonthlyReserveEntry $lancamento): ?bool
    {
        return $lancamento->delete();
    }

    public function somarPorReservaId(int $reservaId): float
    {
        return (float) $this->model
            ->query()
            ->where('monthly_reserve_id', $reservaId)
            ->sum('amount');
    }
}
