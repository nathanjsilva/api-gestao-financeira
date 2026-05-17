<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TransactionRepository
{
    public function __construct(
        protected Transaction $model,
    ) {}

    public function listarPorUsuarioIdECompetencia(int $userId, string $competency): Collection
    {
        return $this->model
            ->query()
            ->with('category:id,name,type')
            ->where('user_id', $userId)
            ->where('competency', $competency)
            ->latest()
            ->get();
    }

    public function buscarPorIdEUsuarioId(int $id, int $userId): ?Transaction
    {
        return $this->model
            ->query()
            ->with('category:id,name,type')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function criar(array $data): Transaction
    {
        return $this->model->query()->create($data);
    }

    public function atualizar(Transaction $transaction, array $data): bool
    {
        return $transaction->update($data);
    }

    public function excluir(Transaction $transaction): ?bool
    {
        return $transaction->delete();
    }

    public function obterResumoMensalPorTipo(int $userId, string $competency): Collection
    {
        return $this->model
            ->query()
            ->select('type', DB::raw('SUM(amount) as total'))
            ->where('user_id', $userId)
            ->where('competency', $competency)
            ->groupBy('type')
            ->get();
    }

    public function obterResumoMensalPorTipoEntreCompetencias(int $userId, array $competencias): Collection
    {
        return $this->model
            ->query()
            ->select(
                'competency',
                'type',
                DB::raw('SUM(amount) as total')
            )
            ->where('user_id', $userId)
            ->whereIn('competency', $competencias)
            ->groupBy('competency', 'type')
            ->get();
    }

    public function obterTotaisDeGastosAgrupadosPorCategoria(int $userId, string $competency): Collection
    {
        return $this->model
            ->query()
            ->select(
                'category_id',
                DB::raw('SUM(amount) as total')
            )
            ->with('category:id,name,type')
            ->where('user_id', $userId)
            ->where('competency', $competency)
            ->where('type', 'expense')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();
    }

    public function obterTotaisDeGastosPorCategoriaEntreCompetencias(
        int $userId,
        string $competenciaInicial,
        string $competenciaFinal,
    ): Collection {
        return $this->model
            ->query()
            ->select(
                'category_id',
                'competency',
                DB::raw('SUM(amount) as total')
            )
            ->with('category:id,name,type')
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereIn('competency', [$competenciaInicial, $competenciaFinal])
            ->groupBy('category_id', 'competency')
            ->orderBy('category_id')
            ->get();
    }
}
