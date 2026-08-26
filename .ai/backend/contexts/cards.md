# context: cards — Módulo de Cartões (cartões, categorias, compras e parcelas)

## Responsabilidade

Gerenciamento detalhado de gastos em cartão: cadastro de cartões e de categorias próprias de cartão, lançamento de compras à vista ou parceladas com geração automática de parcelas, e consolidação/dashboard mensal.

Este módulo é **totalmente aditivo e isolado** do restante do sistema: não referencia `categories`/`transactions`, não altera o dashboard financeiro geral (`FinancialAnalyticsService`) e não recalcula nem migra as `Transaction` antigas usadas historicamente para registrar o valor total gasto no cartão (essas continuam existindo normalmente, tipicamente como uma `Transaction` de `expense` com alguma categoria genérica). A partir da implantação deste módulo, novos lançamentos detalhados de cartão devem ser feitos aqui.

---

## Arquivos Envolvidos

| Tipo | Arquivo |
|------|---------|
| Controllers | `CardController`, `CardCategoryController`, `CardPurchaseController`, `CardDashboardController` |
| Services | `CardService`, `CardCategoryService`, `CardPurchaseService`, `CardAnalyticsService`, `CardDashboardService` |
| Repositories | `CardRepository`, `CardCategoryRepository`, `CardPurchaseRepository`, `CardInstallmentRepository` |
| Models | `Card`, `CardCategory`, `CardPurchase`, `CardInstallment` |
| Requests | `Card/*`, `CardCategory/*`, `CardPurchase/*`, `CardDashboard/*` |
| Resources | `CardResource`, `CardCategoryResource`, `CardPurchaseResource`, `CardInstallmentResource`, `CardDashboardAnalyticsResource`, `CardMonthlySummaryResource` |
| Evento/Cache | `CartaoDadosAlterados` + `InvalidarCacheCartoes` (versão de cache própria: `cards:user:{id}:version`) |
| Migrations | `database/migrations/2026_08_25_*` (`cards`, `card_categories`, `card_purchases`, `card_installments`) |

---

## Estrutura dos Models

### Card
```
id, user_id, name, responsible_person, active (default true)
```
Uma pessoa pode ter múltiplos cartões (sem unicidade em `responsible_person`).

### CardCategory
```
id, user_id, name (unique por usuário), active (default true)
```

### CardPurchase (cabeçalho da compra)
```
id, user_id, card_id, card_category_id, description, total_amount, purchase_date,
reference_competency (YYYY-MM — competência da 1ª parcela EM COBRANÇA, ou seja, da parcela `starting_installment_number`),
payment_type (cash|installment), installments_total, starting_installment_number
```

### CardInstallment (parcela individual — fact table, desnormalizada)
```
id, card_purchase_id, user_id, card_id, card_category_id, payment_type (copiados da compra),
installment_number, competency (YYYY-MM), amount
```
Toda compra gera pelo menos 1 `CardInstallment` — compra à vista gera exatamente 1 (`installment_number = 1`). As agregações mensais somam sempre `card_installments`, nunca `card_purchases.total_amount`.

---

## Endpoints

| Método | Rota | Descrição |
|--------|------|-----------|
| GET/POST/PUT/DELETE | `/api/cards` | CRUD de cartões |
| GET/POST/PUT/DELETE | `/api/card-categories` | CRUD de categorias de cartão |
| GET/POST/PUT/DELETE | `/api/card-purchases` | CRUD de compras (`?competency=&card_id=&card_category_id=&payment_type=`) |
| GET | `/api/card-dashboard/analytics?competency=&months=` | Painel analítico (cache 5min) |
| GET | `/api/card-dashboard/monthly-summary?competency=` | Resumo mensal |

---

## Algoritmo de Parcelamento (`CardPurchaseService::calcularParcelas`)

Divide o valor total em centavos (`intdiv`), distribuindo o resto da divisão para as **últimas** parcelas — garante que a soma feche exatamente com o valor total (ex.: R$1000/3 = 333.33 + 333.33 + 333.34).

Ao cadastrar uma compra que já está em andamento (`starting_installment_number = K > 1`), apenas as parcelas de K até N são persistidas — mas o valor de cada uma é o mesmo que teria na divisão original de N parcelas (não recalcula a divisão sobre `N - K + 1`).

**Edição de compra = apagar e regerar todas as parcelas** (dentro de uma `DB::transaction`) — parcelas são dado derivado, não há diff incremental parcela-a-parcela.

**Quitação** não é uma coluna persistida — `is_settled` é calculado comparando a maior `competency` das parcelas da compra com o mês atual (`CardPurchaseResource` / `CardPurchaseService::verificarQuitacao`).

---

## Regras de Negócio

1. Isolamento por usuário em todas as camadas.
2. `card_id` e `card_category_id` de uma compra devem pertencer ao usuário autenticado e estar **ativos** (tanto na criação quanto na edição).
3. Excluir um cartão ou categoria com compras vinculadas lança `DomainException` (422) — o Service verifica antes (`possuiComprasVinculadas`); a FK também tem `restrictOnDelete()` como proteção adicional em nível de banco.
4. Compra à vista sempre normaliza para `installments_total = 1` e `starting_installment_number = 1`, independentemente do que vier no request (normalização feita no `CardPurchaseService`, não no FormRequest).
5. O valor de cada parcela nunca é digitado — sempre calculado pelo backend.

---

## Cache

Chave própria, **não compartilhada** com o dashboard financeiro geral:
```
"cards:analytics:user:{userId}:{competency}:{months}:v{versao}"
```
Versão incrementada via `Cache::increment("cards:user:{userId}:version")` sempre que `CartaoDadosAlterados` é disparado (criar/atualizar/excluir cartão, categoria ou compra).

---

## `CardDashboardService::obterPainelAnalitico` — blocos retornados

`overview` (total mês/ano), `by_card`, `by_person` (agrupado via 1 JOIN em `cards`, já que `responsible_person` não é desnormalizado nas parcelas), `by_category` (`ranking`, `top_growth`, `concentration`), `evolution` (série mensal), `payment_type_breakdown` (à vista x parcelado), `committed_future` (parcelas com `competency > atual`), `outstanding_balance` (idem, restrito a `payment_type = installment`), `top_categories`, `top_cards`, `insights` (gerados por `CardAnalyticsService`: gasto atípico vs. média histórica, categoria dominante, categoria em alta, parcelas futuras).

---

## Pontos de Atenção

- `CardAnalyticsService` é deliberadamente **independente** do `CategoryAnalyticsService` do dashboard financeiro geral, mesmo tendo lógica parecida — evita acoplar os dois domínios.
- Se o usuário desativar um cartão/categoria, compras já lançadas continuam existindo e aparecendo nas consultas — apenas não podem mais ser usadas em **novas** compras nem em edições de compras existentes.
- `by_card`/`by_person`/`by_category` (e `top_categories`/`top_cards`) retornam dados brutos das Repositories (Collections de models/stdClass) — os Resources (`CardDashboardAnalyticsResource`, `CardMonthlySummaryResource`) são responsáveis por achatar isso em arrays simples antes de serializar, seguindo o mesmo padrão do `DashboardResource` existente.
