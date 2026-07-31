<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReserveAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $saldo = $this->saldo_info;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'active' => $this->active,
            'current_balance' => $saldo['current_balance'] ?? null,
            'previous_balance' => $saldo['previous_balance'] ?? null,
            'delta' => $saldo['delta'] ?? null,
            'is_inherited' => $saldo['is_inherited'] ?? null,
            'note' => $saldo['note'] ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
