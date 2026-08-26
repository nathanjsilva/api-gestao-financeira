<?php

namespace App\Repositories;

use App\Models\CardPurchase;
use Illuminate\Database\Eloquent\Collection;

class CardPurchaseRepository
{
    public function __construct(
        protected CardPurchase $model,
    ) {}

    /**
     * @param  array{competency?: string, card_id?: int, card_category_id?: int, payment_type?: string}  $filtros
     */
    public function listarPorUsuarioId(int $userId, array $filtros = []): Collection
    {
        return $this->model
            ->query()
            ->with(['card:id,name,responsible_person', 'category:id,name', 'installments'])
            ->where('user_id', $userId)
            ->when(
                isset($filtros['competency']),
                fn ($query) => $query->whereHas(
                    'installments',
                    fn ($installments) => $installments->where('competency', $filtros['competency'])
                )
            )
            ->when($filtros['card_id'] ?? null, fn ($query, $cardId) => $query->where('card_id', $cardId))
            ->when(
                $filtros['card_category_id'] ?? null,
                fn ($query, $categoryId) => $query->where('card_category_id', $categoryId)
            )
            ->when(
                $filtros['payment_type'] ?? null,
                fn ($query, $paymentType) => $query->where('payment_type', $paymentType)
            )
            ->latest()
            ->get();
    }

    public function buscarPorIdEUsuarioId(int $id, int $userId): ?CardPurchase
    {
        return $this->model
            ->query()
            ->with(['card:id,name,responsible_person', 'category:id,name', 'installments'])
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function criar(array $dados): CardPurchase
    {
        return $this->model->query()->create($dados);
    }

    public function atualizar(CardPurchase $compra, array $dados): bool
    {
        return $compra->update($dados);
    }

    public function excluir(CardPurchase $compra): ?bool
    {
        return $compra->delete();
    }

    public function obterCompetenciaFinalPorCompraId(int $compraId): ?string
    {
        return $this->model
            ->query()
            ->whereKey($compraId)
            ->withMax('installments as ultima_competencia', 'competency')
            ->value('ultima_competencia');
    }
}
