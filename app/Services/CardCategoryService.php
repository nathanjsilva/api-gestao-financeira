<?php

namespace App\Services;

use App\Events\CartaoDadosAlterados;
use App\Models\CardCategory;
use App\Repositories\CardCategoryRepository;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class CardCategoryService
{
    public function __construct(
        protected CardCategoryRepository $cardCategoryRepository,
    ) {}

    public function listar(int $usuarioId, bool $incluirInativas = false): Collection
    {
        return $this->cardCategoryRepository->listarPorUsuarioId($usuarioId, $incluirInativas);
    }

    public function buscarPorId(int $id, int $usuarioId): ?CardCategory
    {
        return $this->cardCategoryRepository->buscarPorIdEUsuarioId($id, $usuarioId);
    }

    public function criar(int $usuarioId, array $dados): CardCategory
    {
        $categoria = $this->cardCategoryRepository->criar($usuarioId, $dados);

        event(new CartaoDadosAlterados($usuarioId));

        return $categoria;
    }

    public function atualizar(int $id, int $usuarioId, array $dados): CardCategory
    {
        $categoria = $this->buscarPorIdOuFalhar($id, $usuarioId);

        $this->cardCategoryRepository->atualizar($categoria, $dados);

        event(new CartaoDadosAlterados($usuarioId));

        return $categoria->refresh();
    }

    public function excluir(int $id, int $usuarioId): void
    {
        $categoria = $this->buscarPorIdOuFalhar($id, $usuarioId);

        if ($this->cardCategoryRepository->possuiComprasVinculadas($categoria->id)) {
            throw new DomainException('Nao e possivel excluir uma categoria com compras vinculadas. Inative-a ao inves de excluir.');
        }

        $this->cardCategoryRepository->excluir($categoria);

        event(new CartaoDadosAlterados($usuarioId));
    }

    protected function buscarPorIdOuFalhar(int $id, int $usuarioId): CardCategory
    {
        $categoria = $this->buscarPorId($id, $usuarioId);

        if ($categoria !== null) {
            return $categoria;
        }

        throw new DomainException('Categoria de cartao nao encontrada para este usuario.');
    }
}
