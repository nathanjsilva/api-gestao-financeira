<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardMonthlySummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'competency' => $this['competency'],
            'total_month' => $this['total_month'],
            'total_year' => $this['total_year'],
            'by_card' => collect($this['by_card'] ?? [])
                ->map(fn (object $item): array => [
                    'card_id' => $item->card_id,
                    'card_name' => $item->card?->name,
                    'responsible_person' => $item->card?->responsible_person,
                    'total' => round((float) $item->total, 2),
                ])
                ->values(),
            'by_person' => collect($this['by_person'] ?? [])
                ->map(fn (object $item): array => [
                    'responsible_person' => $item->responsible_person,
                    'total' => round((float) $item->total, 2),
                ])
                ->values(),
            'by_category' => collect($this['by_category'] ?? [])
                ->map(fn (object $item): array => [
                    'card_category_id' => $item->card_category_id,
                    'category_name' => $item->category?->name,
                    'total' => round((float) $item->total, 2),
                ])
                ->values(),
            'committed_future' => $this['committed_future'],
            'outstanding_balance' => $this['outstanding_balance'],
        ];
    }
}
