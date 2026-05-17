<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Collection<int, object> $gastosPorCategoria */
        $gastosPorCategoria = collect($this['expenses_by_category'] ?? []);

        return [
            'competency' => $this['competency'],
            'total_income' => $this['total_income'],
            'total_expense' => $this['total_expense'],
            'remaining_amount' => $this['remaining_amount'],
            'previous_reserve' => $this['previous_reserve'],
            'current_reserve' => $this['current_reserve'],
            'investment' => $this['investment'],
            'total_saved' => $this['total_saved'],
            'highest_expense_category' => $this['highest_expense_category'],
            'lowest_expense_category' => $this['lowest_expense_category'],
            'expenses_by_category' => $gastosPorCategoria->map(function (object $item): array {
                return [
                    'category_id' => $item->category_id,
                    'category_name' => $item->category?->name,
                    'total' => round((float) $item->total, 2),
                ];
            })->values(),
        ];
    }
}
