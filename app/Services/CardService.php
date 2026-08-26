<?php

namespace App\Services;

use App\Events\CartaoDadosAlterados;
use App\Models\Card;
use App\Repositories\CardRepository;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class CardService
{
    public function __construct(
        protected CardRepository $cardRepository,
    ) {}

    public function listar(int $usuarioId, bool $incluirInativos = false): Collection
    {
        return $this->cardRepository->listarPorUsuarioId($usuarioId, $incluirInativos);
    }

    public function buscarPorId(int $id, int $usuarioId): ?Card
    {
        return $this->cardRepository->buscarPorIdEUsuarioId($id, $usuarioId);
    }

    public function criar(int $usuarioId, array $dados): Card
    {
        $cartao = $this->cardRepository->criar($usuarioId, $dados);

        event(new CartaoDadosAlterados($usuarioId));

        return $cartao;
    }

    public function atualizar(int $id, int $usuarioId, array $dados): Card
    {
        $cartao = $this->buscarPorIdOuFalhar($id, $usuarioId);

        $this->cardRepository->atualizar($cartao, $dados);

        event(new CartaoDadosAlterados($usuarioId));

        return $cartao->refresh();
    }

    public function excluir(int $id, int $usuarioId): void
    {
        $cartao = $this->buscarPorIdOuFalhar($id, $usuarioId);

        if ($this->cardRepository->possuiComprasVinculadas($cartao->id)) {
            throw new DomainException('Nao e possivel excluir um cartao com compras vinculadas. Inative-o ao inves de excluir.');
        }

        $this->cardRepository->excluir($cartao);

        event(new CartaoDadosAlterados($usuarioId));
    }

    protected function buscarPorIdOuFalhar(int $id, int $usuarioId): Card
    {
        $cartao = $this->buscarPorId($id, $usuarioId);

        if ($cartao !== null) {
            return $cartao;
        }

        throw new DomainException('Cartao nao encontrado para este usuario.');
    }
}
