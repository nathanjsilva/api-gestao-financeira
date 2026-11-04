# Referência local de consumo da API

Esta referência resume a API atual. Para validações completas e fórmulas, consulte docs/fluxograma-arquitetura-aplicacao.md e os Requests em app/Http/Requests.

## Base, autenticação e respostas

Base local: http://localhost:8000/api

Cadastro e login são públicos. As outras rotas abaixo exigem Authorization: Bearer TOKEN e Accept: application/json. A API deriva user_id do token.

Cadastro:

~~~http
POST /auth/register
Content-Type: application/json

{
  "name": "Usuário de exemplo",
  "email": "usuario@example.test",
  "password": "senha-de-exemplo",
  "password_confirmation": "senha-de-exemplo"
}
~~~

Login usa email e password em POST /auth/login. As respostas de cadastro/login têm message e data com id, name, email e token. Cadastro retorna 201; login retorna 200; credenciais inválidas retornam 401. POST /auth/logout revoga o token atual e retorna 200.

Respostas de Resource usam envelope data. Em geral: 200 para consulta/atualização, 201 para criação, 204 para exclusão, 401 sem autenticação, 404 para recurso ausente ou sem acesso e 422 para validação/regra de domínio. Os status exatos dependem da rota.

Competências usam YYYY-MM. Tipos gerais: income e expense. Status de transação: paid e pending. Pagamento de cartão: cash e installment.

## Rotas

Todas as rotas usam o prefixo /api.

| Área | Rotas |
|---|---|
| Categorias gerais | GET/POST /categories; GET/PUT/PATCH/DELETE /categories/{id} |
| Transações | GET/POST /transactions; GET/PUT/PATCH/DELETE /transactions/{id} |
| Reserva mensal | GET/POST /monthly-reserves; GET/PUT/PATCH/DELETE /monthly-reserves/{id} |
| Itens de investimento | GET/POST /monthly-reserves/{reserve}/entries; PUT/PATCH/DELETE /monthly-reserves/{reserve}/entries/{entry} |
| Contas de reserva | GET/POST /reserve-accounts; PUT/PATCH /reserve-accounts/{id} |
| Snapshots por conta | GET /reserve-accounts/{id}/entries; PUT/DELETE /reserve-accounts/{id}/entries/{YYYY-MM} |
| Snapshots em lote | PUT /reserve-account-balances |
| Dashboard geral | GET /dashboard/analytics, /monthly-summary, /category-comparison, /monthly-evolution, /month-comparison |
| Cartões | CRUD em /cards, /card-categories e /card-purchases |
| Dashboard de cartões | GET /card-dashboard/analytics e /card-dashboard/monthly-summary |

## Categorias e transações

Categoria:

~~~json
{
  "name": "Mercado",
  "type": "expense"
}
~~~

Transação:

~~~json
{
  "category_id": 1,
  "description": "Compra do mês",
  "amount": 250.90,
  "type": "expense",
  "status": "paid",
  "competency": "2026-05",
  "is_recurring": false
}
~~~

Store exige todos os campos, inclusive is_recurring. Update aceita campos parciais. A categoria precisa pertencer ao usuário e ter o mesmo tipo da transação. GET /transactions exige competency.

## Reserva e snapshots

MonthlyReserve exige competency única por usuário e investimento numérico não negativo. observations é opcional. O campo reserva_anterior não existe mais.

~~~json
{
  "competency": "2026-05",
  "investimento": 300.00,
  "observations": "Observação do mês"
}
~~~

Snapshot individual:

~~~json
{
  "balance": 1200.00,
  "note": "Saldo declarado"
}
~~~

Lote de snapshots:

~~~json
{
  "competency": "2026-05",
  "entries": [
    {
      "account_id": 1,
      "balance": 1200.00,
      "note": "Saldo declarado"
    }
  ]
}
~~~

O lote exige pelo menos uma conta, IDs distintos e contas do usuário; gravação é atômica. A API aceita conta arquivada se pertencer ao usuário. O snapshot é saldo declarado, não movimentação. A conta é arquivada ou reativada com PUT/PATCH /reserve-accounts/{id}; não há rota de exclusão de conta. Nas rotas individuais de snapshot, a rota verifica o formato YYYY-MM, mas o código atual não valida o número do mês com CompetencyRule; informe sempre uma competência válida. A listagem com filtro e o endpoint em lote fazem a validação completa.

Itens em /monthly-reserves/{id}/entries recalculam investimento como soma dos itens ao criar, editar ou excluir. A API também aceita valor direto de investimento no registro mensal.

## Dashboards

Todos os filtros de competência devem usar YYYY-MM. O dashboard financeiro geral aceita months de 2 a 12, padrão 6. O dashboard de cartões aceita months de 2 a 24, padrão 6.

- /dashboard/monthly-summary exige competency.
- /dashboard/category-comparison exige current_competency e previous_competency.
- /dashboard/monthly-evolution exige start_competency e end_competency em ordem cronológica.
- /dashboard/month-comparison exige first_competency e second_competency.
- /dashboard/analytics exige competency.
- /card-dashboard/monthly-summary exige competency.
- /card-dashboard/analytics exige competency.

O dashboard geral inclui transações paid e pending, saldos vigentes das contas ativas e investimento mensal. O dashboard de cartões agrega parcelas futuras por competência. Os painéis e seus caches são separados.

## Cartões

Card e CardCategory podem ser listados com include_inactive=1 para gerenciamento. Compras aceitam filtros opcionais competency, card_id, card_category_id e payment_type.

Exemplo de compra parcelada:

~~~json
{
  "card_id": 1,
  "card_category_id": 1,
  "description": "Compra de exemplo",
  "total_amount": 1000.00,
  "purchase_date": "2026-05-10",
  "reference_competency": "2026-05",
  "payment_type": "installment",
  "installments_total": 3,
  "starting_installment_number": 1
}
~~~

Cartão e categoria precisam pertencer ao usuário e estar ativos. Para cash, o backend normaliza o cronograma para uma parcela. Update de compra exige os campos completos; o backend apaga e recria as parcelas em transação. Excluir cartão ou categoria com compra vinculada é bloqueado; arquive o registro.

installment_schedule_ended indica que o cronograma previsto alcançou sua competência final. is_settled é mantido por compatibilidade e não confirma pagamento. Não há fatura, vencimento ou quitação real.