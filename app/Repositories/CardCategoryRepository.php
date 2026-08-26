<?php

namespace App\Repositories;

use App\Models\CardCategory;
use Illuminate\Database\Eloquent\Collection;

class CardCategoryRepository
{
    public function __construct(
        protected CardCategory $model,
    ) {}

    public function listarPorUsuarioId(int $userId, bool $incluirInativas = false): Collection
    {
        return $this->model
            ->query()
            ->where('user_id', $userId)
            ->when(! $incluirInativas, fn ($query) => $query->where('active', true))
            ->orderBy('name')
            ->get();
    }

    public function buscarPorIdEUsuarioId(int $id, int $userId): ?CardCategory
    {
        return $this->model
            ->query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function possuiComprasVinculadas(int $cardCategoryId): bool
    {
        return $this->model
            ->query()
            ->whereKey($cardCategoryId)
            ->whereHas('purchases')
            ->exists();
    }

    public function criar(int $userId, array $dados): CardCategory
    {
        return $this->model->query()->create([
            'user_id' => $userId,
            'name' => $dados['name'],
            'active' => $dados['active'] ?? true,
        ]);
    }

    public function atualizar(CardCategory $categoria, array $dados): bool
    {
        return $categoria->update($dados);
    }

    public function excluir(CardCategory $categoria): ?bool
    {
        return $categoria->delete();
    }
}
