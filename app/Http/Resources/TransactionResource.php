<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'description' => $this->description,
            'amount' => $this->amount,
            'type' => $this->type,
            'status' => $this->status,
            'competency' => $this->competency,
            'is_recurring' => $this->is_recurring,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
