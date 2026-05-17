<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function __construct(
        protected CategoryRepository $categoryRepository,
    ) {}

    public function listar(int $usuarioId): Collection
    {
        return $this->categoryRepository->listarPorUsuarioId($usuarioId);
    }

    public function buscarPorId(int $id, int $usuarioId): ?Category
    {
        return $this->categoryRepository->buscarPorIdEUsuarioId($id, $usuarioId);
    }

    public function criar(int $usuarioId, array $dados): Category
    {
        return $this->categoryRepository->criar([
            'user_id' => $usuarioId,
            'name' => $dados['name'],
            'type' => $dados['type'],
        ]);
    }

    public function atualizar(int $id, int $usuarioId, array $dados): Category
    {
        $categoria = $this->buscarCategoriaOuFalhar($id, $usuarioId);

        $this->categoryRepository->atualizar($categoria, $dados);

        return $categoria->refresh();
    }

    public function excluir(int $id, int $usuarioId): void
    {
        $categoria = $this->buscarCategoriaOuFalhar($id, $usuarioId);

        $this->categoryRepository->excluir($categoria);
    }

    protected function buscarCategoriaOuFalhar(int $id, int $usuarioId): Category
    {
        $categoria = $this->buscarPorId($id, $usuarioId);

        if ($categoria !== null) {
            return $categoria;
        }

        throw new DomainException('Categoria nao encontrada para este usuario.');
    }
}
