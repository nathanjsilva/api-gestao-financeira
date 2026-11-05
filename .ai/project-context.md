# Contexto geral do projeto

## Visão geral

API Gestão Financeira é uma aplicação com backend Laravel e uma SPA Vue independente. O backend expõe a API JSON em /api; a SPA roda a partir de frontend/index.html e se comunica com a API por HTTP. A rota web / do Laravel ainda serve a view welcome, não a SPA.

| Camada | Tecnologia atual |
|---|---|
| API | Composer PHP constraint ^8.3 (Docker image PHP 8.4), Laravel 13, Laravel Sanctum |
| SPA | Vue 3, TypeScript, Vue Router, Pinia, Axios, Tailwind CSS 4, ApexCharts |
| Build da SPA | Vite 6, configuração em frontend/vite.config.ts |
| Build das views Laravel | Vite 8, configuração na raiz em vite.config.ts |
| Persistência | Eloquent e banco relacional configurado pelo ambiente; Docker Compose fornece MySQL 8 |
| Cache | Cache do Laravel; padrão database, driver configurável; o Compose também fornece Redis |
| Execução local | API Laravel em localhost:8000 e Vite da SPA em localhost:5173 |

Os módulos de cartões têm categorias, compras e painéis próprios. Seus dados não são integrados às categorias, transações ou ao dashboard financeiro geral.

## Modelos e relações principais

| Modelo | Dados e relações relevantes |
|---|---|
| User | Usuário autenticado; possui categorias, transações, reservas mensais, contas de reserva e entidades do módulo de cartões. |
| Category | user_id, name, type (income ou expense); única por usuário, nome e tipo. Possui transações. |
| Transaction | user_id, category_id, description, amount, type, status (paid ou pending), competency (YYYY-MM) e is_recurring. A categoria precisa pertencer ao usuário e ter o mesmo tipo. |
| MonthlyReserve | user_id, competency, investimento e observations; único por usuário e competência. Não possui mais reserva_anterior. Possui itens de investimento. |
| MonthlyReserveEntry | monthly_reserve_id, description e amount; itens que recalculam o total investimento quando criados, editados ou removidos. |
| ReserveAccount | user_id, name e active; conta nomeada de reserva, arquivada por active=false em vez de excluída. |
| ReserveAccountEntry | reserve_account_id, competency, balance e note; snapshot de saldo declarado, único por conta e competência. Competências sem snapshot herdam o último valor anterior. |
| Card | user_id, name, responsible_person e active. |
| CardCategory | user_id, name e active; única por usuário e nome. É independente de Category. |
| CardPurchase | user_id, card_id, card_category_id, descrição, valor total, data, competência inicial e dados de pagamento parcelado. |
| CardInstallment | parcelas derivadas da compra, com usuário, cartão, categoria, tipo, número, competência e valor. Os painéis de cartões agregam parcelas, não o total do cabeçalho. |

Detalhes, restrições e cálculos de cada domínio estão nos contextos específicos:

- [Autenticação](backend/contexts/auth.md)
- [Categorias](backend/contexts/categories.md)
- [Transações](backend/contexts/transactions.md)
- [Reserva mensal](backend/contexts/monthly-reserve.md)
- [Dashboard geral](backend/contexts/dashboard.md)
- [Módulo backend de cartões](backend/contexts/cards.md)
- [Módulo frontend de cartões](frontend/cards.md)
- [Mapa do frontend](frontend/overview.md)

## Dependências entre módulos

- Transações dependem de uma categoria geral do mesmo usuário e do mesmo tipo.
- Alterar o tipo de uma categoria não reclassifica transações existentes; a compatibilidade é conferida pelo TransactionService na criação/atualização da transação.
- Dashboard geral agrega transações, snapshots das contas de reserva ativas e o investimento mensal. Transações pagas e pendentes entram nos totais.
- Reserva mensal reúne snapshots de contas de reserva e itens de investimento. Os snapshots são saldos, não movimentos de caixa.
- Compras de cartão dependem de cartão e categoria de cartão ativos do mesmo usuário. O backend gera parcelas; o dashboard de cartões lê essas parcelas.
- Categorias e compras de cartão não alteram transações. O analytics do dashboard geral exibe as parcelas em `card_expenses`, separadas dos totais de transações, do fluxo de caixa e das categorias financeiras.
- Mutação de dados financeiros invalida o cache do dashboard geral. Mutação de cartão, categoria de cartão ou compra invalida os caches dos dois analytics que exibem esses dados.

## API

Base relativa: /api. Cadastro e login são públicos; as demais rotas abaixo exigem auth:sanctum. Logout também exige autenticação e revoga o token atual.

| Área | Rotas e parâmetros principais |
|---|---|
| Autenticação | POST /auth/register, POST /auth/login, POST /auth/logout. |
| Categorias | CRUD em /categories. |
| Transações | CRUD em /transactions; GET exige competency=YYYY-MM. |
| Reserva mensal | CRUD em /monthly-reserves; itens em /monthly-reserves/{id}/entries. |
| Contas de reserva | GET/POST /reserve-accounts; PUT /reserve-accounts/{id}; entradas em /reserve-accounts/{id}/entries e /reserve-accounts/{id}/entries/{competency}; gravação em lote via PUT /reserve-account-balances. A listagem aceita competency e include_archived. |
| Dashboard geral | GET /dashboard/analytics, /monthly-summary, /category-comparison, /monthly-evolution e /month-comparison, sob o prefixo /dashboard. O painel analítico aceita months entre 2 e 12, padrão 6. |
| Cartões | CRUD em /cards, /card-categories e /card-purchases. As listas de cartões e categorias aceitam include_inactive=1. Compras aceitam filtros competency, card_id, card_category_id e payment_type. |
| Dashboard de cartões | GET /card-dashboard/analytics e /card-dashboard/monthly-summary. O analytics aceita months entre 2 e 24, padrão 6. |

Para contratos completos, consulte os contextos dos módulos; este resumo evita duplicar regras detalhadas.

## Frontend

A SPA inicia em frontend/src/main.ts. Rotas e guards ficam em frontend/src/router; páginas em frontend/src/pages; chamadas HTTP em frontend/src/services; sessão em frontend/src/stores/authStore.ts. Os serviços retornam os dados do Resource Laravel e centralizam as chamadas por módulo.

Os scripts atuais da SPA são dev, build, typecheck e preview. O script typecheck executa tsc --noEmit; apesar de vue-tsc estar instalado, o script atual não o invoca. A configuração TypeScript usa strict=false. Consulte frontend/overview.md para rotas, serviços e componentes atuais.

## Infraestrutura

O Docker Compose do projeto fornece a API, MySQL e Redis. A SPA é executada separadamente pelo Vite e não há serviço de frontend no Compose. Variáveis e credenciais dependem do ambiente; não trate valores exemplificativos como segredos ou configurações universais.

## Regras financeiras aprovadas — 2026-11

As descrições antigas dos modelos `MonthlyReserve`, `MonthlyReserveEntry`, `ReserveAccount` e das fórmulas do Dashboard devem ser lidas junto das regras revisadas em `.ai/backend/contexts/monthly-reserve.md` e `.ai/backend/contexts/dashboard.md`. Reservas e Investimentos são itens com saldos totais por competência no módulo compartilhado de Reservas. O fluxo de transações é separado do patrimônio, e investimentos operacionais legados são arquivados e zerados por migration recuperável, sem execução automática no banco real.
