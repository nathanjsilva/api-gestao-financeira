# overview.md — Estrutura do Frontend

## Tecnologias e Versões

```json
{
  "vue": "^3.5.13",
  "vue-router": "^4.6.4",
  "pinia": "^3.0.4",
  "axios": "^1.16.1",
  "tailwindcss": "^4.3.0",
  "vite": "^6.0.5"
}
```

**Dev server**: `vite --host 0.0.0.0 --port 5173`
**Build**: `vite build`

---

## Roteamento (Vue Router 4)

### Rotas Disponíveis

| Path | Nome | Layout | Guard | Componente |
|------|------|--------|-------|-----------|
| `/login` | `login` | PUBLIC | guestOnly | `pages/auth/LoginPage.vue` |
| `/cadastro` | `register` | PUBLIC | guestOnly | `pages/auth/RegisterPage.vue` |
| `/dashboard` | `dashboard` | AUTHENTICATED | requiresAuth | `pages/dashboard/DashboardPage.vue` |
| `/categorias` | `categories` | AUTHENTICATED | requiresAuth | `pages/categories/CategoriesPage.vue` |
| `/transacoes` | `transactions` | AUTHENTICATED | requiresAuth | `pages/transactions/TransactionsPage.vue` |
| `/reserva-mensal` | `monthly-reserve` | AUTHENTICATED | requiresAuth | `pages/monthly-reserve/MonthlyReservePage.vue` |

- `/` redireciona para `/dashboard` (autenticado) ou `/login` (não autenticado)
- Nomes das rotas em `constants/routeNames.js`

### Guards de Rota

**authGuard.js** — Protege rotas autenticadas:
```javascript
// Se não há token → redireciona para { name: LOGIN }
```

**guestGuard.js** — Protege rotas públicas:
```javascript
// Se já há token → redireciona para { name: DASHBOARD }
```

---

## Layouts

### AuthenticatedLayout.vue
- Menu de navegação lateral/superior
- Links para: Dashboard, Categorias, Transações, Reserva Mensal
- Botão de logout
- Slot para conteúdo da página

### PublicLayout.vue
- Layout simples sem navegação
- Para páginas de login e cadastro

---

## Estado Global (Pinia)

### authStore.js

```javascript
// Estado
user  // ref — objeto do usuário logado
token // ref — Bearer token

// Computados
isAuthenticated // computed — boolean
userName        // computed — nome do usuário

// Ações
setSession(payload)  // salva user + token no localStorage
clearSession()       // limpa dados locais

// Storage key: 'gestao_financeira_auth'
```

---

## Serviços HTTP

### httpClient.js (Axios)
- Base URL: `VITE_API_BASE_URL` (default: `http://localhost:8000/api`)
- **Request interceptor**: Adiciona `Authorization: Bearer {token}` de todo request
- **Response interceptor**: Em 401 → limpa sessão + redireciona para `/login`

### Services por Módulo

```javascript
// services/auth/authService.js
register(payload)  // POST /auth/register
login(payload)     // POST /auth/login
logout()           // POST /auth/logout

// services/categories/categoryService.js
list()                    // GET /categories
create(payload)           // POST /categories
update(id, payload)       // PUT /categories/{id}
remove(id)                // DELETE /categories/{id}

// services/transactions/transactionService.js
list(params)              // GET /transactions?competency=YYYY-MM
create(payload)           // POST /transactions
update(id, payload)       // PUT /transactions/{id}
remove(id)                // DELETE /transactions/{id}

// services/monthly-reserves/monthlyReserveService.js
list()                    // GET /monthly-reserves
create(payload)           // POST /monthly-reserves
update(id, payload)       // PUT /monthly-reserves/{id}
remove(id)                // DELETE /monthly-reserves/{id}
listEntries(reserveId)    // GET /monthly-reserves/{id}/entries (lançamentos de investimento)
createEntry(reserveId, payload)
updateEntry(reserveId, entryId, payload)
removeEntry(reserveId, entryId)

// services/reserve-accounts/reserveAccountService.js
list(params)               // GET /reserve-accounts (?competency=YYYY-MM)
create(payload)             // POST /reserve-accounts
update(id, payload)         // PUT /reserve-accounts/{id} (renomear/arquivar)
listEntries(id)              // GET /reserve-accounts/{id}/entries
setEntry(id, competency, payload)    // PUT /reserve-accounts/{id}/entries/{competency}
removeEntry(id, competency)          // DELETE /reserve-accounts/{id}/entries/{competency}

// services/dashboard/dashboardService.js
analytics(params)         // GET /dashboard/analytics
monthlySummary(competency)// GET /dashboard/monthly-summary
categoryComparison(params)// GET /dashboard/category-comparison
monthlyEvolution(params)  // GET /dashboard/monthly-evolution
monthComparison(params)   // GET /dashboard/month-comparison
```

---

## Componentes

### Base (`components/base/`)
| Componente | Props principais | Uso |
|-----------|-----------------|-----|
| `BaseButton.vue` | `type`, `disabled`, `loading` | Botão padrão |
| `BaseCard.vue` | — | Wrapper de card |
| `BaseInput.vue` | `label`, `modelValue`, `error` | Input com label |
| `BaseSelect.vue` | `label`, `modelValue`, `options`, `error` | Select com label |
| `BaseTextarea.vue` | `label`, `modelValue`, `error` | Textarea com label |

### Dashboard (`components/dashboard/`)
| Componente | Dados esperados |
|-----------|----------------|
| `CashFlowChart.vue` | `cash_flow` do analytics |
| `CategoryRanking.vue` | `categories` do analytics |
| `ExpenseChart.vue` | dados de despesas |
| `FinancialCard.vue` | `overview` / métricas individuais |
| `FinancialHeatmap.vue` | `heatmap` do analytics |
| `FinancialInsights.vue` | `insights` do analytics |
| `MonthlyComparison.vue` | `comparison` do analytics |
| `ReserveEvolution.vue` | `reserve_evolution` do analytics |

### Transações (`components/transactions/`)
- `TransactionCard.vue` — exibe uma transação (description, amount, category, status)
- `TransactionStatusBadge.vue` — badge colorido para `paid`/`pending`

---

## Composables

### useLoading.js
```javascript
const { isLoading, withLoading } = useLoading()

// Uso
await withLoading(async () => {
  await transactionService.create(payload)
})
// isLoading é true durante a execução
```

### useFormErrors.js
```javascript
const { generalError, fieldError, clearErrors, setErrorsFromApi } = useFormErrors()

// Uso no catch
setErrorsFromApi(error) // parse automático de erros da API Laravel (422)

// No template
fieldError('email')     // erro do campo 'email'
generalError            // erro geral (não de campo)
```

---

## Constants

### routeNames.js
```javascript
export const LOGIN         = 'login'
export const REGISTER      = 'register'
export const DASHBOARD     = 'dashboard'
export const CATEGORIES    = 'categories'
export const TRANSACTIONS  = 'transactions'
export const MONTHLY_RESERVE = 'monthly-reserve'
```

### transactions.js
```javascript
export const TRANSACTION_TYPES = [
  { label: 'Entrada', value: 'income' },
  { label: 'Saída',   value: 'expense' }
]

export const TRANSACTION_STATUS = [
  { label: 'Pago',     value: 'paid' },
  { label: 'Pendente', value: 'pending' }
]
```

### layouts.js
```javascript
export const PUBLIC        = 'PublicLayout'
export const AUTHENTICATED = 'AuthenticatedLayout'
```

---

## Helpers

### competency.js
```javascript
getCurrentCompetency(date = new Date()) // → 'YYYY-MM' (ex: '2026-07')
```

### currency.js
```javascript
formatCurrency(value) // → 'R$ 1.234,56' (Intl.NumberFormat, locale pt-BR)
```

### percentage.js
```javascript
formatPercentage(value) // → '36,0%'
```

### apiError.js
```javascript
// Parse de erros da API para uso no useFormErrors
```
