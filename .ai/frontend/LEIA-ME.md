# LEIA-ME.md — Regras Obrigatórias do Frontend

## LEIA ESTE ARQUIVO ANTES DE QUALQUER ALTERAÇÃO NO FRONTEND

---

## Stack Obrigatória

| Tecnologia | Versão | Uso |
|-----------|--------|-----|
| Vue 3 | ^3.5.13 | Framework principal |
| Composition API | — | **Obrigatório** — não usar Options API |
| `<script setup>` | — | **Obrigatório** em todos os componentes |
| Pinia | ^3.0.4 | Estado global (apenas authStore) |
| Vue Router 4 | ^4.6.4 | Roteamento SPA |
| Axios | ^1.16.1 | HTTP via httpClient.js |
| Tailwind CSS 4 | ^4.3.0 | Estilização (sem CSS puro) |
| Vite | ^6.0.5 | Build tool |

---

## Estrutura de Diretórios

```
frontend/src/
├── components/      ← componentes reutilizáveis (sem lógica de negócio)
│   ├── base/        ← BaseButton, BaseCard, BaseInput, BaseSelect, BaseTextarea
│   ├── dashboard/   ← componentes do dashboard
│   ├── data-display/← EmptyState, MetricCard
│   ├── layout/      ← PageHeader
│   ├── reserve/     ← ReserveChart
│   ├── shared/      ← FinancialInsight
│   └── transactions/← TransactionCard, TransactionStatusBadge
├── pages/           ← páginas completas, uma por rota
├── stores/          ← apenas authStore.js (Pinia)
├── services/        ← chamadas HTTP por módulo
├── router/          ← index.js + routes.js
├── middleware/      ← authGuard.js + guestGuard.js
├── composables/     ← useLoading.js + useFormErrors.js
├── constants/       ← routeNames.js, api.js, transactions.js, layouts.js
├── helpers/         ← competency.js, currency.js, percentage.js, apiError.js
└── layouts/         ← AuthenticatedLayout.vue + PublicLayout.vue
```

---

## Regras de Componentes

1. **Sempre use `<script setup>`** — nunca `defineComponent()` ou Options API
2. **Componentes base (`base/`)** são os blocos de construção — use-os, não reescreva HTML puro
3. **Não coloque lógica de negócio em componentes** — lógica fica nas pages ou composables
4. **Props com tipos explícitos** — sempre declare `defineProps` com tipos
5. **Emits explícitos** — sempre declare `defineEmits`

---

## Regras de Serviços HTTP

- **Nunca use `axios` diretamente** — sempre via `httpClient.js`
- Cada módulo tem seu próprio service em `services/{módulo}/`
- O `httpClient.js` já injeta o Bearer token automaticamente
- Em 401, o interceptor redireciona para `/login` automaticamente

---

## Regras de Formulários

- Use `useFormErrors.js` para gerenciar erros de formulário
- Use `useLoading.js` para estados de carregamento
- Sempre desabilite o botão de submit durante loading
- Sempre exiba erros por campo (`fieldError(name)`) e erros gerais (`generalError`)

---

## Formatação de Dados

- **Moeda**: use `formatCurrency(value)` de `helpers/currency.js` — nunca formate manualmente
- **Percentual**: use `formatPercentage(value)` de `helpers/percentage.js`
- **Competência atual**: use `getCurrentCompetency()` de `helpers/competency.js`

---

## Roteamento

- Nomes de rotas: use constantes de `constants/routeNames.js`
- Nunca use strings hardcoded para nomes de rota
- Rotas autenticadas: `authGuard.js` (redireciona para login se não autenticado)
- Rotas públicas: `guestGuard.js` (redireciona para dashboard se já autenticado)

---

## O que NÃO fazer

- Não usar `Options API` (`data()`, `methods:`, `computed:`)
- Não acessar `axios` diretamente (sempre via services)
- Não criar CSS customizado sem necessidade (use classes Tailwind)
- Não duplicar componentes — verifique se já existe em `components/`
- Não hardcodar cores ou espaçamentos (use tokens do Tailwind)
- Não usar `localStorage` diretamente no componente — use `authStore`
- Não usar `console.log` em código que vai para produção
