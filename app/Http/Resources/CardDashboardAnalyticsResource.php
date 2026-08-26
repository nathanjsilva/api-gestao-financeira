<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class CardDashboardAnalyticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'filters' => $this['filters'],
            'overview' => $this['overview'],
            'by_card' => $this->mapPorCartao(collect($this['by_card'] ?? [])),
            'by_person' => $this->mapPorPessoa(collect($this['by_person'] ?? [])),
            'by_category' => $this['by_category'],
            'evolution' => $this['evolution'],
            'payment_type_breakdown' => $this->mapPorTipoPagamento(collect($this['payment_type_breakdown'] ?? [])),
            'committed_future' => $this['committed_future'],
            'outstanding_balance' => $this['outstanding_balance'],
            'top_categories' => $this['top_categories'],
            'top_cards' => $this->mapPorCartao(collect($this['top_cards'] ?? [])),
            'insights' => $this['insights'],
        ];
    }

    private function mapPorCartao(Collection $itens): Collection
    {
        return $itens
            ->map(fn (object $item): array => [
                'card_id' => $item->card_id,
                'card_name' => $item->card?->name,
                'responsible_person' => $item->card?->responsible_person,
                'total' => round((float) $item->total, 2),
            ])
            ->values();
    }

    private function mapPorPessoa(Collection $itens): Collection
    {
        return $itens
            ->map(fn (object $item): array => [
                'responsible_person' => $item->responsible_person,
                'total' => round((float) $item->total, 2),
            ])
            ->values();
    }

    private function mapPorTipoPagamento(Collection $itens): Collection
    {
        return $itens
            ->map(fn (object $item): array => [
                'payment_type' => $item->payment_type,
                'total' => round((float) $item->total, 2),
            ])
            ->values();
    }
}
