# feature-index.md — Índice de Funcionalidades

## Funcionalidades do Sistema

| Funcionalidade | Área | Context Backend | Página Frontend | Store |
|----------------|------|-----------------|-----------------|-------|
| Autenticação (login/cadastro/logout) | Auth | `.ai/backend/contexts/auth.md` | `pages/auth/` | `authStore.js` |
| Categorias (CRUD) | Financeiro | `.ai/backend/contexts/categories.md` | `pages/categories/CategoriesPage.vue` | — |
| Transações (CRUD + filtro por mês) | Financeiro | `.ai/backend/contexts/transactions.md` | `pages/transactions/TransactionsPage.vue` | — |
| Reserva Mensal (contas de reserva + investimentos) | Financeiro | `.ai/backend/contexts/monthly-reserve.md` | `pages/monthly-reserve/MonthlyReservePage.vue` | — |
| Dashboard Analítico | Analytics | `.ai/backend/contexts/dashboard.md` | `pages/dashboard/DashboardPage.vue` | — |

---

## Mapa de Arquivos por Funcionalidade

### Autenticação
```
Backend:
  app/Http/Controllers/Api/AuthController.php
  app/Services/AuthService.php
  app/Repositories/UserRepository.php
  app/Http/Requests/Auth/RegisterRequest.php
  app/Http/Requests/Auth/LoginRequest.php

Frontend:
  frontend/src/pages/auth/LoginPage.vue
  frontend/src/pages/auth/RegisterPage.vue
  frontend/src/stores/authStore.js
  frontend/src/services/auth/authService.js
  frontend/src/middleware/authGuard.js
  frontend/src/middleware/guestGuard.js
```

### Categorias
```
Backend:
  app/Http/Controllers/Api/CategoryController.php
  app/Services/CategoryService.php
  app/Repositories/CategoryRepository.php
  app/Http/Requests/Category/StoreCategoryRequest.php
  app/Http/Requests/Category/UpdateCategoryRequest.php
  app/Http/Resources/CategoryResource.php

Frontend:
  frontend/src/pages/categories/CategoriesPage.vue
  frontend/src/services/categories/categoryService.js
```

### Transações
```
Backend:
  app/Http/Controllers/Api/TransactionController.php
  app/Services/TransactionService.php
  app/Repositories/TransactionRepository.php
  app/Http/Requests/Transaction/StoreTransactionRequest.php
  app/Http/Requests/Transaction/UpdateTransactionRequest.php
  app/Http/Requests/Transaction/ListTransactionRequest.php
  app/Http/Resources/TransactionResource.php
  app/Rules/CompetencyRule.php

Frontend:
  frontend/src/pages/transactions/TransactionsPage.vue
  frontend/src/services/transactions/transactionService.js
  frontend/src/components/transactions/TransactionCard.vue
  frontend/src/components/transactions/TransactionStatusBadge.vue
```

### Reserva Mensal
```
Backend (investimentos, mantido):
  app/Http/Controllers/Api/MonthlyReserveController.php
  app/Http/Controllers/Api/MonthlyReserveEntryController.php
  app/Services/MonthlyReserveService.php
  app/Repositories/MonthlyReserveRepository.php
  app/Repositories/MonthlyReserveEntryRepository.php
  app/Http/Requests/MonthlyReserve/*.php
  app/Http/Resources/MonthlyReserveResource.php
  app/Http/Resources/MonthlyReserveEntryResource.php

Backend (contas de reserva, novo):
  app/Http/Controllers/Api/ReserveAccountController.php
  app/Http/Controllers/Api/ReserveAccountEntryController.php
  app/Services/ReserveAccountService.php
  app/Repositories/ReserveAccountRepository.php
  app/Repositories/ReserveAccountEntryRepository.php
  app/Http/Requests/ReserveAccount/*.php
  app/Http/Resources/ReserveAccountResource.php
  app/Http/Resources/ReserveAccountEntryResource.php

Frontend:
  frontend/src/pages/monthly-reserve/MonthlyReservePage.vue
  frontend/src/services/monthly-reserves/monthlyReserveService.js
  frontend/src/services/reserve-accounts/reserveAccountService.js
  frontend/src/components/reserve/ReserveChart.vue
  frontend/src/components/reserve/ReserveAccountRow.vue
```

### Dashboard Analítico
```
Backend:
  app/Http/Controllers/Api/DashboardController.php
  app/Services/DashboardService.php
  app/Services/FinancialAnalyticsService.php
  app/Services/CategoryAnalyticsService.php
  app/Services/MonthlyComparisonService.php
  app/Http/Requests/Dashboard/*.php (5 requests)
  app/Http/Resources/Dashboard*.php (5 resources)

Frontend:
  frontend/src/pages/dashboard/DashboardPage.vue
  frontend/src/services/dashboard/dashboardService.js
  frontend/src/components/dashboard/CashFlowChart.vue
  frontend/src/components/dashboard/CategoryRanking.vue
  frontend/src/components/dashboard/ExpenseChart.vue
  frontend/src/components/dashboard/FinancialCard.vue
  frontend/src/components/dashboard/FinancialHeatmap.vue
  frontend/src/components/dashboard/FinancialInsights.vue
  frontend/src/components/dashboard/MonthlyComparison.vue
  frontend/src/components/dashboard/ReserveEvolution.vue
```

---

## Componentes Compartilhados (Frontend)

```
frontend/src/components/base/
  BaseButton.vue    — Botão padrão reutilizável
  BaseCard.vue      — Card container
  BaseInput.vue     — Input com label e erro
  BaseSelect.vue    — Select com label e erro
  BaseTextarea.vue  — Textarea com label e erro

frontend/src/components/data-display/
  EmptyState.vue    — Estado vazio (sem dados)
  MetricCard.vue    — Card de métrica numérica

frontend/src/components/layout/
  PageHeader.vue    — Cabeçalho de página com título e ações

frontend/src/components/shared/
  FinancialInsight.vue — Item de insight financeiro
```

---

## Utilitários (Frontend)

```
frontend/src/helpers/
  competency.js     — getCurrentCompetency(date) → YYYY-MM
  currency.js       — formatCurrency(value) → R$ X.XXX,XX
  percentage.js     — formatPercentage(value) → X,X%
  apiError.js       — parse de erros da API

frontend/src/composables/
  useLoading.js     — isLoading + withLoading(fn)
  useFormErrors.js  — generalError, fieldError(name), setErrorsFromApi(error)

frontend/src/constants/
  routeNames.js     — LOGIN, REGISTER, DASHBOARD, CATEGORIES, TRANSACTIONS, MONTHLY_RESERVE
  transactions.js   — TRANSACTION_TYPES, TRANSACTION_STATUS
  api.js            — API_BASE_URL
  layouts.js        — PUBLIC, AUTHENTICATED
```
