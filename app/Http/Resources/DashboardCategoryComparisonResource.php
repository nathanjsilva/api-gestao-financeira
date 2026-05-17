<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardCategoryComparisonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'current_competency' => $this['current_competency'],
            'previous_competency' => $this['previous_competency'],
            'categories' => collect($this['categories'])->values(),
        ];
    }
}
