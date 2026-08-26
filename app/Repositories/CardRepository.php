<?php

namespace App\Repositories;

use App\Models\Card;
use Illuminate\Database\Eloquent\Collection;

class CardRepository
{
    public function __construct(
        protected Card $model,
    ) {}

    public function listarPorUsuarioId(int $userId, bool $incluirInativos = false): Collection
    {
        return $this->model
            ->query()
            ->where('user_id', $userId)
            ->when(! $incluirInativos, fn ($query) => $query->where('active', true))
            ->orderBy('name')
            ->get();
    }

    public function buscarPorIdEUsuarioId(int $id, int $userId): ?Card
    {
        return $this->model
            ->query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function possuiComprasVinculadas(int $cardId): bool
    {
        return $this->model
            ->query()
            ->whereKey($cardId)
            ->whereHas('purchases')
            ->exists();
    }

    public function criar(int $userId, array $dados): Card
    {
        return $this->model->query()->create([
            'user_id' => $userId,
            'name' => $dados['name'],
            'responsible_person' => $dados['responsible_person'],
            'active' => $dados['active'] ?? true,
        ]);
    }

    public function atualizar(Card $card, array $dados): bool
    {
        return $card->update($dados);
    }

    public function excluir(Card $card): ?bool
    {
        return $card->delete();
    }
}
