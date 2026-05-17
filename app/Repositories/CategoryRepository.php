<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    public function __construct(
        protected Category $model,
    ) {}

    public function listarPorUsuarioId(int $userId): Collection
    {
        return $this->model
            ->query()
            ->where('user_id', $userId)
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    public function buscarPorIdEUsuarioId(int $id, int $userId): ?Category
    {
        return $this->model
            ->query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function buscarPorIdTipoEUsuarioId(int $id, int $userId, string $type): ?Category
    {
        return $this->model
            ->query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->where('type', $type)
            ->first();
    }

    public function criar(array $data): Category
    {
        return $this->model->query()->create($data);
    }

    public function atualizar(Category $category, array $data): bool
    {
        return $category->update($data);
    }

    public function excluir(Category $category): ?bool
    {
        return $category->delete();
    }
}
