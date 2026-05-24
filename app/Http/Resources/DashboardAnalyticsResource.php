<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardAnalyticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'filters' => $this['filters'],
            'overview' => $this['overview'],
            'comparison' => $this['comparison'],
            'kpis' => $this['kpis'],
            'categories' => $this['categories'],
            'evolution' => $this['evolution'],
            'reserve_evolution' => $this['reserve_evolution'],
            'cash_flow' => $this['cash_flow'],
            'heatmap' => $this['heatmap'],
            'insights' => $this['insights'],
        ];
    }
}

