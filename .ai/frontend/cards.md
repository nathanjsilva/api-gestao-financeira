# context: cards — Frontend do Módulo de Cartões

## Responsabilidade

Telas de cadastro de cartões, categorias de cartão, lançamento de compras (à vista/parceladas) e um dashboard analítico dedicado — tudo agrupado sob a seção "Cartões", isolada do dashboard financeiro geral.

---

## Navegação

Um único item de navegação global "Cartões" (sidebar desktop e bottom tab bar mobile — `AuthenticatedLayout.vue`, agora com 5 itens) leva a `/cartoes/compras`. Dentro da seção, a sub-navegação (`components/cards/CardsSubNav.vue`, renderizada no topo de cada uma das 4 páginas) alterna entre as abas:

| Path | Rota | Página |
|------|------|--------|
| `/cartoes/compras` | `card-purchases` | `pages/card-purchases/CardPurchasesPage.vue` |
| `/cartoes/gerenciar` | `cards` | `pages/cards/CardsPage.vue` |
| `/cartoes/categorias` | `card-categories` | `pages/card-categories/CardCategoriesPage.vue` |
| `/cartoes/analise` | `card-dashboard` | `pages/card-dashboard/CardDashboardPage.vue` |

Essa IA em sub-abas evita inflar a navegação global com 4 novos itens (ver `.ai/frontend/overview.md`).

---

## Services

```javascript
// services/cards/cardService.js — list/create/update/remove (mirror de categoryService.js)
// services/card-categories/cardCategoryService.js — idem
// services/card-purchases/cardPurchaseService.js — list(params)/create/update/remove
// services/card-dashboard/cardDashboardService.js — analytics(params)/monthlySummary(competency)
```

---

## Componentes

```
components/cards/
  CardsSubNav.vue        — sub-navegação das 4 páginas do módulo
  CardForm.vue            — nome, pessoa responsável, toggle ativo (no modal de edição)

components/card-categories/
  CardCategoryForm.vue     — nome, toggle ativo

components/card-purchases/
  CardPurchaseForm.vue     — cartão, categoria, descrição, valor, data, tipo de pagamento,
                             parcelas — com PREVIEW AO VIVO de cada parcela antes de salvar
                             (helpers/cardInstallments.js replica o algoritmo de arredondamento
                             do backend para o preview bater exatamente com o que será salvo)
  CardPurchaseCard.vue     — card mobile de uma compra
  InstallmentStatusBadge.vue — badge "À vista/Parcelado Nx" + "Quitada/Em andamento"

components/card-dashboard/
  CardEvolutionChart.vue   — área/linha, evolução mensal (ApexCharts)
  CardCategoryChart.vue    — donut, gastos por categoria (mirror de ExpenseChart.vue)
  CardPaymentTypeChart.vue — donut, à vista x parcelado
  CardBreakdownList.vue    — lista de barras genérica (usada para "por cartão" e "por pessoa")
```

---

## Helpers

```javascript
// helpers/cardInstallments.js
calculateInstallments(totalAmount, installmentsTotal, startingInstallmentNumber, referenceCompetency)
// Replica CardPurchaseService::calcularParcelas do backend — usado só para o preview do form,
// o valor real sempre vem do backend na resposta da API.
```

---

## Constants

```javascript
// constants/cardPurchases.js
CARD_PAYMENT_TYPES = [{ label: 'À vista', value: 'cash' }, { label: 'Parcelado', value: 'installment' }]
```

`routeNames.js` ganhou `CARD_PURCHASES`, `CARDS`, `CARD_CATEGORIES`, `CARD_DASHBOARD`.

---

## Pontos de Atenção

- `CardsPage.vue`/`CardCategoriesPage.vue` listam por padrão apenas itens **ativos** (mesmo comportamento de `ReserveAccount` — desativar é uma espécie de arquivamento; não há tela de "reativar" um item já inativo, mirror do padrão já aceito em `reserve/` para contas arquivadas).
- Em `CardPurchasesPage.vue`, o valor exibido na coluna/coluna "parcela do mês" da listagem **não é** `total_amount` da compra — é o valor da parcela específica cuja `competency` bate com o filtro selecionado (`installments` vêm sempre carregadas junto com a compra).
- O dashboard de cartões (`/cartoes/analise`) é completamente separado do dashboard financeiro geral (`/dashboard`) — nenhum dado se mistura entre os dois.
