<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonthlyReserveResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $saldo = $this->saldo_calculado;

        return [
            'id' => $this->id,
            'competency' => $this->competency,
            'investimento' => $this->investimento,
            'observations' => $this->observations,
            'total_income' => $saldo['total_income'] ?? null,
            'total_expense' => $saldo['total_expense'] ?? null,
            'remaining_amount' => $saldo['remaining_amount'] ?? null,
            'current_reserve' => $saldo['current_reserve'] ?? null,
            'total_saved' => $saldo['total_saved'] ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
