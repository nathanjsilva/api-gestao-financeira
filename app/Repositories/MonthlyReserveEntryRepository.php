<?php

namespace App\Repositories;

use App\Models\MonthlyReserveEntry;
use Illuminate\Database\Eloquent\Collection;

class MonthlyReserveEntryRepository
{
    public function __construct(
        protected MonthlyReserveEntry $model,
    ) {}

    public function listarPorReservaId(int $reservaId): Collection
    {
        return $this->model
            ->query()
            ->where('monthly_reserve_id', $reservaId)
            ->orderByDesc('created_at')
            ->get();
    }
}
