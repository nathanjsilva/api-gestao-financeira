# Mapa do frontend

> Este documento registra estrutura e comportamento observáveis. As regras normativas para agentes estão em [AGENTS.md](../../AGENTS.md); os detalhes funcionais estão nos contextos de cada módulo.

## Stack e execução

A SPA usa Vue 3, TypeScript, Vue Router, Pinia, Axios, Tailwind CSS 4 e ApexCharts. O entrypoint é frontend/src/main.ts e a configuração do frontend fica em frontend/vite.config.ts. Esta SPA usa Vite 6 e roda separadamente do build das views Laravel, cuja configuração fica na raiz do repositório.

Scripts em frontend/package.json:
- dev: Vite em 0.0.0.0:5173;
- build: vite build;
- typecheck: tsc --noEmit;
- preview: Vite em 0.0.0.0:4173.

vue-tsc está instalado, mas os scripts atuais não o chamam. O tsconfig inclui .ts, .d.ts e .vue, define strict=false e allowJs=false. Assim, o typecheck configurado executa tsc e não verifica integralmente os blocos TypeScript dos componentes Vue.

## Rotas

| Caminho | Página | Acesso |
|---|---|---|
| /login | pages/auth/LoginPage.vue | Visitante; autenticados são redirecionados. |
| /cadastro | pages/auth/RegisterPage.vue | Visitante; autenticados são redirecionados. |
| /dashboard | pages/dashboard/DashboardPage.vue | Autenticado. |
| /categorias | pages/categories/CategoriesPage.vue | Autenticado. |
| /transacoes | pages/transactions/TransactionsPage.vue | Autenticado. |
| /reserva-mensal | pages/monthly-reserve/MonthlyReservePage.vue | Autenticado. |
| /cartoes/compras | pages/card-purchases/CardPurchasesPage.vue | Autenticado; compras e análises. |
| /cartoes/gerenciar | pages/cards/CardsPage.vue | Autenticado; cartões ativos e arquivados. |
| /cartoes/categorias | pages/card-categories/CardCategoriesPage.vue | Autenticado; categorias ativas e arquivadas. |
| /cartoes/analise | — | Redirect de compatibilidade para /cartoes/compras. |

A raiz redireciona para o dashboard quando autenticado ou para login quando visitante. AuthenticatedLayout contém a navegação privada e PublicLayout atende login e cadastro. As três páginas de cartões compartilham CardsSubNav; o módulo aparece como um item na navegação global.

## Sessão e cliente HTTP

- stores/authStore.ts mantém usuário e token e persiste a sessão em localStorage.
- middleware/authGuard.ts e guestGuard.ts controlam a navegação no navegador. A autorização da API continua sendo responsabilidade do backend e do Sanctum.
- services/api/httpClient.ts usa VITE_API_BASE_URL, com padrão http://localhost:8000/api, envia Bearer token quando existe e limpa a sessão/redireciona em respostas 401.
- Cada service do módulo extrai response.data.data e centraliza as chamadas HTTP.

## Páginas e serviços por módulo

| Módulo | Página e service principal | Dependências na tela |
|---|---|---|
| Autenticação | pages/auth; services/auth/authService.ts | authStore e httpClient. |
| Categorias | pages/categories; services/categories/categoryService.ts | CategoryForm e opções de tipo. |
| Transações | pages/transactions; services/transactions/transactionService.ts | categorias, filtros locais por texto/categoria/tipo/status/recorrência, resumo da competência e gráfico de gastos por categoria. |
| Reserva mensal | pages/monthly-reserve; services/monthly-reserves/monthlyReserveService.ts e services/reserve-accounts/reserveAccountService.ts | MonthlyReserveEditor também chama dashboardService e edita snapshots, itens de investimento, observações e contas. |
| Dashboard geral | pages/dashboard; services/dashboard/dashboardService.ts | Resumo, comparações, ranking, evolução, reservas, fluxo de caixa, heatmap, insights e KPIs. |
| Cartões | páginas em pages/cards, card-categories e card-purchases; services/cards, card-categories, card-purchases e card-dashboard | Ver [contexto frontend de cartões](cards.md). |

MonthlyReserveEditor carrega contas da competência incluindo arquivadas, apresenta contas ativas e arquivadas, permite criar, renomear, arquivar e reativar, e prepara os saldos das contas ativas para gravação em lote. A gravação dos saldos é atômica no endpoint de lote. Renomeações são requests separados e não fazem parte dessa atomicidade. O componente também gerencia os itens de investimento da reserva mensal.

TransactionsPage filtra localmente a lista mensal que recebeu da API e apresenta totais e análise visual. O status de uma transação não é usado como critério dos agregados do backend; consulte o contexto de transações.

DashboardPage envia competency e months ao endpoint analytics. O backend aceita meses entre 2 e 12, embora o input da tela não limite localmente esse intervalo. O painel apresenta entradas, gastos, saldo restante, total guardado, comparação, insights, gráficos e KPIs do período.

## Componentes compartilhados

| Diretório/arquivo | Responsabilidade |
|---|---|
| components/base | BaseButton, BaseCard, BaseInput, BaseSelect, BaseTextarea, BaseModal, BaseMonthPicker e BasePagination. |
| components/data-display | EmptyState e MetricCard. |
| components/layout | PageHeader. |
| components/shared | FinancialInsight. |
| composables | useFormErrors, useLoading e usePagination. |
| helpers | apiError, cardInstallments, competency, currency e percentage. |
| constants | api, cardPurchases, layouts, routeNames e transactions. |

O helper cardInstallments.ts replica no navegador apenas a prévia do algoritmo de parcelas; o cálculo persistido pelo backend é a autoridade. Para os componentes do módulo de cartões e a semântica da previsão, consulte frontend/cards.md.

## Inicialização

- frontend/src/main.ts registra Pinia e Vue Router, importa os estilos e monta App.vue.
- frontend/src/router/index.ts cria o router a partir de router/routes.ts.
- frontend/index.html aponta para frontend/src/main.ts.