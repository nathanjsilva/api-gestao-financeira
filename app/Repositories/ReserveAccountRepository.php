<?php

namespace App\Repositories;

use App\Models\ReserveAccount;
use Illuminate\Database\Eloquent\Collection;

class ReserveAccountRepository
{
    public function __construct(
        protected ReserveAccount $model,
    ) {}

    public function listarPorUsuarioId(int $userId, bool $incluirArquivadas = false): Collection
    {
        return $this->model
            ->query()
            ->where('user_id', $userId)
            ->with('statusEntries')
            ->when(! $incluirArquivadas, fn ($query) => $query->where('active', true))
            ->orderBy('name')
            ->get();
    }

    public function buscarPorIdEUsuarioId(int $id, int $userId): ?ReserveAccount
    {
        return $this->model
            ->query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->with('statusEntries')
            ->first();
    }

    public function criar(int $userId, array $dados): ReserveAccount
    {
        return $this->model->query()->create([
            'user_id' => $userId,
            'name' => $dados['name'],
            'type' => $dados['type'] ?? 'reserve',
            'active' => true,
            'archive_status_needs_review' => false,
        ]);
    }

    public function atualizar(ReserveAccount $reserveAccount, array $dados): bool
    {
        return $reserveAccount->update($dados);
    }
}
