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
            'description' => $this->name,
            'type' => $this->type,
            'active' => $this->active_at_competency ?? $this->active,
            'current_active' => $this->active,
            'archive_status_needs_review' => $this->archive_status_needs_review,
            'effective_status_competency' => $this->effective_status_competency
                ?? $this->statusEntries->last()?->competency,
            'status_history' => $this->whenLoaded('statusEntries', fn () => $this->statusEntries->map(fn ($status): array => [
                'competency' => $status->competency,
                'active' => $status->active,
            ])->values()),
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
