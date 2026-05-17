<?php

namespace App\Repositories;

use App\Models\MonthlyReserve;
use Illuminate\Database\Eloquent\Collection;

class MonthlyReserveRepository
{
    public function __construct(
        protected MonthlyReserve $model,
    ) {}

    public function listarPorUsuarioId(int $userId): Collection
    {
        return $this->model
            ->query()
            ->where('user_id', $userId)
            ->orderByDesc('competency')
            ->get();
    }

    public function buscarPorUsuarioIdECompetencia(int $userId, string $competency): ?MonthlyReserve
    {
        return $this->model
            ->query()
            ->where('user_id', $userId)
            ->where('competency', $competency)
            ->first();
    }

    public function obterPorUsuarioIdECompetencias(int $userId, array $competencias): Collection
    {
        return $this->model
            ->query()
            ->where('user_id', $userId)
            ->whereIn('competency', $competencias)
            ->get();
    }

    public function buscarPorIdEUsuarioId(int $id, int $userId): ?MonthlyReserve
    {
        return $this->model
            ->query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function criar(array $data): MonthlyReserve
    {
        return $this->model->query()->create($data);
    }

    public function atualizar(MonthlyReserve $monthlyReserve, array $data): bool
    {
        return $monthlyReserve->update($data);
    }

    public function excluir(MonthlyReserve $monthlyReserve): ?bool
    {
        return $monthlyReserve->delete();
    }
}
