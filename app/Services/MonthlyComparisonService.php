<?php

namespace App\Services;

class MonthlyComparisonService
{
    public function comparar(array $atual, array $anterior): array
    {
        $gastosAtual = (float) $atual['total_expense'];
        $gastosAnterior = (float) $anterior['total_expense'];
        $entradasAtual = (float) $atual['total_income'];
        $entradasAnterior = (float) $anterior['total_income'];

        return [
            'previous_competency' => $anterior['competency'],
            'current_competency' => $atual['competency'],
            'expense_difference' => round($gastosAtual - $gastosAnterior, 2),
            'expense_percentage' => $this->calcularPercentual($gastosAtual, $gastosAnterior),
            'income_difference' => round($entradasAtual - $entradasAnterior, 2),
            'income_percentage' => $this->calcularPercentual($entradasAtual, $entradasAnterior),
            'reserve_difference' => round($atual['current_reserve'] - $anterior['current_reserve'], 2),
            'remaining_difference' => round($atual['remaining_amount'] - $anterior['remaining_amount'], 2),
            'trend' => $this->definirTendencia($gastosAtual, $gastosAnterior),
        ];
    }

    public function calcularTaxaEconomia(float $valorRestante, float $entradas): float
    {
        if ($entradas <= 0) {
            return 0;
        }

        return round(($valorRestante / $entradas) * 100, 2);
    }

    public function calcularRendaComprometida(float $gastos, float $entradas): float
    {
        if ($entradas <= 0) {
            return 0;
        }

        return round(($gastos / $entradas) * 100, 2);
    }

    protected function calcularPercentual(float $atual, float $anterior): float
    {
        if ($anterior <= 0) {
            return $atual > 0 ? 100 : 0;
        }

        return round((($atual - $anterior) / $anterior) * 100, 2);
    }

    protected function definirTendencia(float $gastosAtual, float $gastosAnterior): string
    {
        if ($gastosAtual > $gastosAnterior) {
            return 'worse';
        }

        if ($gastosAtual < $gastosAnterior) {
            return 'better';
        }

        return 'stable';
    }
}

