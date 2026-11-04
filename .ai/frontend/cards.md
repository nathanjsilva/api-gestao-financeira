# Contexto frontend: módulo de cartões

## Responsabilidade e navegação

O módulo oferece gerenciamento de cartões, categorias próprias de cartão, compras à vista ou parceladas e análise de parcelas por competência. É independente de Categorias, Transações e Dashboard financeiro geral. Compras e análises são apresentadas juntas na página de compras.

| Rota | Página | Função |
|---|---|---|
| /cartoes/compras | pages/card-purchases/CardPurchasesPage.vue | Cadastro, pesquisa, filtros, totais e análises. |
| /cartoes/gerenciar | pages/cards/CardsPage.vue | Criar, editar, arquivar e reativar cartões. |
| /cartoes/categorias | pages/card-categories/CardCategoriesPage.vue | Criar, editar, arquivar e reativar categorias de cartão. |
| /cartoes/analise | — | Redirect de compatibilidade para /cartoes/compras. |

As três páginas usam components/cards/CardsSubNav.vue. A navegação global tem um único item Cartões.

## Serviços e componentes

Services em TypeScript:
- services/cards/cardService.ts;
- services/card-categories/cardCategoryService.ts;
- services/card-purchases/cardPurchaseService.ts;
- services/card-dashboard/cardDashboardService.ts.

Componentes principais:
- components/cards/CardForm.vue e CardsSubNav.vue;
- components/card-categories/CardCategoryForm.vue;
- components/card-purchases/CardPurchaseForm.vue, CardPurchaseCard.vue e InstallmentStatusBadge.vue;
- components/card-dashboard/CardEvolutionChart.vue, CardCategoryChart.vue, CardPaymentTypeChart.vue e CardBreakdownList.vue.

CardPurchasesPage usa os quatro services, filtra localmente por descrição, cartão, categoria, tipo de pagamento e competência, e monta os totais da competência a partir da parcela correspondente de cada compra. Os gráficos e indicadores analíticos são exibidos na mesma página.

## Gestão de itens inativos

CardsPage e CardCategoriesPage solicitam include_inactive=1 para mostrar registros ativos e inativos e permitir reativação. Os formulários de compra recebem somente cartões e categorias ativos. Compras antigas continuam consultáveis mesmo quando o cartão ou categoria vinculados forem arquivados.

## Parcelas e semântica da tela

O formulário calcula uma prévia das parcelas com helpers/cardInstallments.ts, que replica o arredondamento do backend apenas para apresentação. O backend gera e persiste as parcelas.

A coluna de valor mensal e os totais locais somam a parcela cuja competency corresponde ao filtro, não o total_amount do cabeçalho da compra. Os demais cards, rankings e gráficos seguem o endpoint do dashboard de cartões.

installment_schedule_ended significa que a última competência prevista chegou ou passou. A interface apresenta Previsão encerrada ou Parcelas futuras. O campo de compatibilidade is_settled pode ser usado como fallback de leitura, mas não comprova pagamento. Não há ciclo de fatura, vencimento, baixa, liquidação parcial ou quitação no modelo atual.

Os valores e gráficos de cartões são uma projeção por competência; não entram no dashboard financeiro geral, que se baseia em transactions e reservas. A tela informa essa separação para evitar que os totais sejam interpretados como integração contábil.

## Consultas e dependências

- cardDashboardService.ts consulta /card-dashboard/analytics e /card-dashboard/monthly-summary.
- CardPurchasesPage carrega listas com os services de cartões e categorias, usando registros ativos nos formulários.
- Insights do servidor podem indicar gasto atípico, categoria dominante, categoria em alta ou parcelas futuras.
- Para fórmulas, payloads da API, cache e regras de exclusão, consulte [contexto backend de cartões](../backend/contexts/cards.md).