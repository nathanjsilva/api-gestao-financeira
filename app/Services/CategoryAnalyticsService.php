<?php

namespace App\Services;

use Illuminate\Support\Collection;

class CategoryAnalyticsService
{
    public function montarRanking(Collection $gastosAtuais, Collection $comparativoAnterior): array
    {
        $totalGastos = (float) $gastosAtuais->sum(fn (object $item): float => (float) $item->total);
        $anteriores = $comparativoAnterior->keyBy('category_id');

        return $gastosAtuais
            ->map(function (object $item) use ($totalGastos, $anteriores): array {
                $totalAtual = (float) $item->total;
                $totalAnterior = (float) ($anteriores->get($item->category_id)->total ?? 0);

                return [
                    'category_id' => $item->category_id,
                    'category_name' => $item->category?->name,
                    'total' => round($totalAtual, 2),
                    'previous_total' => round($totalAnterior, 2),
                    'difference' => round($totalAtual - $totalAnterior, 2),
                    'percentage_of_expenses' => $totalGastos > 0 ? round(($totalAtual / $totalGastos) * 100, 2) : 0,
                    'growth_percentage' => $this->calcularCrescimento($totalAtual, $totalAnterior),
                    'trend' => $this->definirTendencia($totalAtual, $totalAnterior),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    public function categoriasQueMaisCresceram(array $ranking): array
    {
        return collect($ranking)
            ->filter(fn (array $item): bool => $item['difference'] > 0)
            ->sortByDesc('difference')
            ->take(3)
            ->values()
            ->all();
    }

    protected function calcularCrescimento(float $atual, float $anterior): float
    {
        if ($anterior <= 0) {
            return $atual > 0 ? 100 : 0;
        }

        return round((($atual - $anterior) / $anterior) * 100, 2);
    }

    protected function definirTendencia(float $atual, float $anterior): string
    {
        if ($atual > $anterior) {
            return 'up';
        }

        if ($atual < $anterior) {
            return 'down';
        }

        return 'stable';
    }
}

