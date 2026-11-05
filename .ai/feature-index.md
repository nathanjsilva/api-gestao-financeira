# Índice de funcionalidades e arquivos

Este índice localiza os contextos funcionais e os principais arquivos atuais. As regras normativas para agentes estão em AGENTS.md. Consulte o contexto do módulo antes de alterar seu código.

## Funcionalidades

| Funcionalidade | Contexto backend | Contexto frontend |
|---|---|---|
| Autenticação | backend/contexts/auth.md | frontend/overview.md |
| Categorias financeiras | backend/contexts/categories.md | frontend/overview.md |
| Transações | backend/contexts/transactions.md | frontend/overview.md |
| Reserva mensal, contas e investimentos | backend/contexts/monthly-reserve.md | frontend/overview.md |
| Dashboard financeiro geral | backend/contexts/dashboard.md | frontend/overview.md |
| Cartões, categorias, compras e parcelas | backend/contexts/cards.md | frontend/cards.md |

## Autenticação

Backend: AuthController, AuthService, UserRepository, RegisterRequest, LoginRequest e User.

Frontend:
- frontend/src/pages/auth/LoginPage.vue
- frontend/src/pages/auth/RegisterPage.vue
- frontend/src/stores/authStore.ts
- frontend/src/services/auth/authService.ts
- frontend/src/middleware/authGuard.ts
- frontend/src/middleware/guestGuard.ts
- frontend/src/services/api/httpClient.ts

## Categorias financeiras

Backend: CategoryController, CategoryService, CategoryRepository, StoreCategoryRequest, UpdateCategoryRequest, CategoryResource e Category.

Frontend:
- frontend/src/pages/categories/CategoriesPage.vue
- frontend/src/components/categories/CategoryForm.vue
- frontend/src/services/categories/categoryService.ts

## Transações

Backend: TransactionController, TransactionService, TransactionRepository, ListTransactionRequest, StoreTransactionRequest, UpdateTransactionRequest, TransactionResource, CompetencyRule e Transaction.

Frontend:
- frontend/src/pages/transactions/TransactionsPage.vue
- frontend/src/components/transactions/TransactionCard.vue
- frontend/src/components/transactions/TransactionForm.vue
- frontend/src/components/transactions/TransactionStatusBadge.vue
- frontend/src/services/transactions/transactionService.ts
- frontend/src/constants/transactions.ts

## Reserva mensal

Backend:
- MonthlyReserveController e MonthlyReserveEntryController
- ReserveAccountController e ReserveAccountEntryController
- MonthlyReserveService e ReserveAccountService
- MonthlyReserveRepository, MonthlyReserveEntryRepository, ReserveAccountRepository e ReserveAccountEntryRepository
- Requests em app/Http/Requests/MonthlyReserve e app/Http/Requests/ReserveAccount
- Resources MonthlyReserveResource, MonthlyReserveEntryResource, ReserveAccountResource e ReserveAccountEntryResource
- Models MonthlyReserve, MonthlyReserveEntry, ReserveAccount e ReserveAccountEntry

Frontend:
- frontend/src/pages/monthly-reserve/MonthlyReservePage.vue
- frontend/src/components/reserve/MonthlyReserveEditor.vue
- frontend/src/components/reserve/ReserveAccountRow.vue
- frontend/src/components/reserve/ReserveChart.vue
- frontend/src/services/monthly-reserves/monthlyReserveService.ts
- frontend/src/services/reserve-accounts/reserveAccountService.ts

## Dashboard financeiro geral

Backend: DashboardController, DashboardService, FinancialAnalyticsService, CategoryAnalyticsService, MonthlyComparisonService, Requests em app/Http/Requests/Dashboard e Resources Dashboard*.

Frontend:
- frontend/src/pages/dashboard/DashboardPage.vue
- frontend/src/services/dashboard/dashboardService.ts
- Componentes em frontend/src/components/dashboard

## Cartões

O fluxo e as regras completas estão em backend/contexts/cards.md e frontend/cards.md.

Backend: CardController, CardCategoryController, CardPurchaseController, CardDashboardController; CardService, CardCategoryService, CardPurchaseService, CardAnalyticsService, CardDashboardService; CardRepository, CardCategoryRepository, CardPurchaseRepository, CardInstallmentRepository; Models Card, CardCategory, CardPurchase e CardInstallment; requests em app/Http/Requests/Card, CardCategory, CardPurchase e CardDashboard; Resources Card*; evento CartaoDadosAlterados e listener InvalidarCacheCartoes.

Frontend:
- frontend/src/pages/card-purchases/CardPurchasesPage.vue
- frontend/src/pages/cards/CardsPage.vue
- frontend/src/pages/card-categories/CardCategoriesPage.vue
- Services em frontend/src/services/cards, card-categories, card-purchases e card-dashboard
- Componentes em frontend/src/components/cards, card-categories, card-purchases e card-dashboard
- frontend/src/helpers/cardInstallments.ts
- frontend/src/constants/cardPurchases.ts

## Arquivos compartilhados do frontend

- Inicialização e configuração: frontend/src/main.ts, frontend/src/App.vue, frontend/src/env.d.ts, frontend/tsconfig.json, frontend/vite.config.ts e frontend/package.json.
- Rotas e sessão: frontend/src/router/index.ts, frontend/src/router/routes.ts, frontend/src/middleware e frontend/src/stores/authStore.ts.
- Cliente HTTP: frontend/src/services/api/httpClient.ts e frontend/src/constants/api.ts.
- Componentes reutilizáveis: frontend/src/components/base, data-display, layout e shared.
- Composables: frontend/src/composables/useFormErrors.ts, useLoading.ts e usePagination.ts.
- Helpers: frontend/src/helpers/apiError.ts, competency.ts, currency.ts e percentage.ts.
- Constantes: frontend/src/constants/layouts.ts, routeNames.ts e transactions.ts.

## Atualização funcional — Reservas e Dashboard (2026-11)

A funcionalidade de reserva mensal agora atende múltiplos itens por usuário, com classificação Reserva/Investimento e snapshots de saldo total por competência. Consulte as regras atualizadas em `backend/contexts/monthly-reserve.md` e `backend/contexts/dashboard.md`; as descrições antigas de aportes e fórmulas foram substituídas.