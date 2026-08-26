<?php

namespace App\Services;

use Illuminate\Support\Collection;

class CardAnalyticsService
{
    public function montarRankingCategorias(Collection $gastosAtuais, Collection $gastosAnteriores): array
    {
        $totalGastos = (float) $gastosAtuais->sum(fn (object $item): float => (float) $item->total);
        $anteriores = $gastosAnteriores->keyBy('card_category_id');

        return $gastosAtuais
            ->map(function (object $item) use ($totalGastos, $anteriores): array {
                $totalAtual = (float) $item->total;
                $totalAnterior = (float) ($anteriores->get($item->card_category_id)->total ?? 0);

                return [
                    'card_category_id' => $item->card_category_id,
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

    public function calcularConcentracao(array $ranking): array
    {
        $top1 = $ranking[0]['percentage_of_expenses'] ?? 0;
        $top3 = round(collect($ranking)->take(3)->sum('percentage_of_expenses'), 2);

        return [
            'top_1_percentage' => $top1,
            'top_3_percentage' => $top3,
        ];
    }

    public function calcularImpactoParcelasFuturas(float $comprometidoFuturo, float $totalMesAtual): float
    {
        if ($totalMesAtual <= 0) {
            return $comprometidoFuturo > 0 ? 100.0 : 0.0;
        }

        return round(($comprometidoFuturo / $totalMesAtual) * 100, 2);
    }

    /**
     * @return array{is_atipico: bool, desvio_percentual: float}
     */
    public function detectarGastoAtipico(float $totalMesAtual, float $mediaHistorica): array
    {
        if ($mediaHistorica <= 0) {
            return [
                'is_atipico' => false,
                'desvio_percentual' => 0.0,
            ];
        }

        $desvioPercentual = round((($totalMesAtual - $mediaHistorica) / $mediaHistorica) * 100, 2);

        return [
            'is_atipico' => $desvioPercentual >= 30.0,
            'desvio_percentual' => $desvioPercentual,
        ];
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
