<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardPurchaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'card_id' => $this->card_id,
            'card' => new CardResource($this->whenLoaded('card')),
            'card_category_id' => $this->card_category_id,
            'category' => new CardCategoryResource($this->whenLoaded('category')),
            'description' => $this->description,
            'total_amount' => $this->total_amount,
            'purchase_date' => $this->purchase_date?->format('Y-m-d'),
            'reference_competency' => $this->reference_competency,
            'payment_type' => $this->payment_type,
            'installments_total' => $this->installments_total,
            'starting_installment_number' => $this->starting_installment_number,
            'is_settled' => $this->whenLoaded(
                'installments',
                fn (): bool => $this->installments->max('competency') <= now()->format('Y-m')
            ),
            'installments' => CardInstallmentResource::collection($this->whenLoaded('installments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
