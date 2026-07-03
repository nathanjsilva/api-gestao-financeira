# context: dashboard — Dashboard Analítico

## Responsabilidade

Endpoints analíticos que agregam dados financeiros para visualização no painel. Inclui resumos, comparações, evolução temporal, ranking de categorias e insights automáticos.

---

## Arquivos Envolvidos

| Tipo | Arquivo |
|------|---------|
| Controller | `app/Http/Controllers/Api/DashboardController.php` |
| Service principal | `app/Services/DashboardService.php` |
| Service analítico | `app/Services/FinancialAnalyticsService.php` |
| Service categorias | `app/Services/CategoryAnalyticsService.php` |
| Service comparação | `app/Services/MonthlyComparisonService.php` |
| Requests (5) | `app/Http/Requests/Dashboard/*.php` |
| Resources (5) | `app/Http/Resources/Dashboard*.php` |

---

## Endpoints

### 1. Painel Analítico Completo
```
GET /api/dashboard/analytics?competency=YYYY-MM&months=6
```
- **Cache**: 5 minutos por `(user_id, competency, months)`
- Retorna os 10 blocos de dados abaixo

**Estrutura da resposta:**
```json
{
  "filters": {
    "competency": "2026-07",
    "months": 6
  },
  "overview": {
    "income": 5000.00,
    "expense": 3200.00,
    "balance": 1800.00
  },
  "comparison": {
    "income_diff": 500.00,
    "income_pct": 11.1,
    "expense_diff": -200.00,
    "expense_pct": -5.9,
    "balance_diff": 700.00
  },
  "kpis": {
    "taxa_economia": 36.0,
    "renda_comprometida": 64.0
  },
  "categories": [
    { "name": "Alimentação", "total": 1200.00, "pct": 37.5, "growth": 5.2 }
  ],
  "evolution": [
    { "competency": "2026-01", "income": 4500.00, "expense": 3000.00, "balance": 1500.00 }
  ],
  "reserve_evolution": [
    { "competency": "2026-01", "reserva_anterior": 10000.00, "investimento": 1500.00, "total": 11500.00 }
  ],
  "cash_flow": [
    { "competency": "2026-07", "income": 5000.00, "expense": 3200.00, "remaining": 1800.00 }
  ],
  "heatmap": {},
  "insights": [
    { "type": "warning", "message": "Gastos com Alimentação cresceram 5.2% em relação ao mês anterior." }
  ]
}
```

### 2. Resumo Mensal
```
GET /api/dashboard/monthly-summary?competency=YYYY-MM
```
- Totais de entradas e saídas do mês com breakdown por categoria

### 3. Comparativo de Categorias
```
GET /api/dashboard/category-comparison?current_competency=YYYY-MM&previous_competency=YYYY-MM
```
- Comparação de gastos por categoria entre dois meses

### 4. Evolução Mensal
```
GET /api/dashboard/monthly-evolution?start_competency=YYYY-MM&end_competency=YYYY-MM
```
- Série temporal de entradas e saídas entre dois períodos

### 5. Comparação entre Meses
```
GET /api/dashboard/month-comparison?first_competency=YYYY-MM&second_competency=YYYY-MM
```
- Comparação detalhada entre dois meses específicos

---

## Fluxo do Painel Analítico

```
GET /api/dashboard/analytics?competency=2026-07&months=6
    → FinancialAnalyticsRequest (valida: competency, months)
    → DashboardController::analytics()
    → FinancialAnalyticsService::obterPainelAnalitico(userId, competencia, meses)
        [Cache::remember(5 min)]
        → TransactionRepository::obterResumoMensalPorTipo()        → overview
        → MonthlyComparisonService::comparar(atual, anterior)       → comparison
        → MonthlyComparisonService::calcularTaxaEconomia()          → kpis
        → CategoryAnalyticsService::montarRanking()                 → categories
        → TransactionRepository::obterResumoMensalPorTipoEntreCompetencias() → evolution
        → MonthlyReserveRepository::obterPorUsuarioIdECompetencias() → reserve_evolution
        → [cálculos de cash_flow, heatmap, insights]
    → DashboardAnalyticsResource
```

---

## Serviços de Suporte

### CategoryAnalyticsService
```php
montarRanking(array $gastosAtuais, array $gastosAnteriores): array
// Retorna ranking de categorias com: total, percentual, crescimento

categoriasQueMaisCresceram(array $ranking): array
// Retorna top 3 categorias com maior crescimento percentual
```

### MonthlyComparisonService
```php
comparar(array $atual, array $anterior): array
// Calcula diferenças absolutas e percentuais entre dois meses

calcularTaxaEconomia(float $valorRestante, float $entradas): float
// Taxa = (restante / entradas) * 100

calcularRendaComprometida(float $gastos, float $entradas): float
// Taxa = (gastos / entradas) * 100
```

---

## Regras de Negócio

1. Todos os cálculos filtram por `user_id` do token autenticado
2. O painel analítico usa cache de 5 minutos — alterações recentes podem não aparecer imediatamente
3. `months` controla quantos meses anteriores são incluídos na evolução (default: 6)
4. `taxa_economia` = quanto % da renda foi poupado
5. `renda_comprometida` = quanto % da renda foi gasto
6. Insights são gerados automaticamente com base nos dados — não são editáveis pelo usuário

---

## Cache

**Chave de cache:**
```php
"dashboard_analytics_{userId}_{competency}_{months}"
```

**TTL**: 5 minutos

**Invalidação**: O cache expira automaticamente. Se necessário invalidar manualmente após uma operação, use:
```php
Cache::forget("dashboard_analytics_{$userId}_{$competency}_{$months}");
```

---

## Requests de Validação

```php
// FinancialAnalyticsRequest
'competency' => ['required', new CompetencyRule()],
'months'     => ['integer', 'min:1', 'max:24'],

// MonthlySummaryRequest
'competency' => ['required', new CompetencyRule()],

// CategoryComparisonRequest
'current_competency'  => ['required', new CompetencyRule()],
'previous_competency' => ['required', new CompetencyRule()],

// MonthlyEvolutionRequest
'start_competency' => ['required', new CompetencyRule()],
'end_competency'   => ['required', new CompetencyRule()],

// MonthComparisonRequest
'first_competency'  => ['required', new CompetencyRule()],
'second_competency' => ['required', new CompetencyRule()],
```

---

## Pontos de Atenção

- O `FinancialAnalyticsService` é o mais complexo do sistema — envolve múltiplos repositórios
- Qualquer alteração neste serviço pode afetar todos os 10 blocos do painel
- O cache pode mascarar bugs recém-corrigidos em desenvolvimento — use `Cache::flush()` se necessário
- Os insights são dinâmicos e dependem de comparações com o mês anterior — sem histórico, alguns não aparecem
