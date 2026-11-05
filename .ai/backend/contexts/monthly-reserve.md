# Contexto: reserva mensal

## Responsabilidade e fontes de dados

O módulo acompanha a reserva patrimonial por competência e combina três fontes:

1. Contas de reserva e seus snapshots de saldo declarado.
2. Transações da competência, usadas para calcular o saldo restante.
3. Reserva mensal e seus itens de investimento.

MonthlyReserve não possui o antigo campo reserva_anterior. ReserveAccountEntry guarda snapshots, não movimentações; se uma competência não tem declaração, o saldo herda o snapshot mais recente de competência anterior. MonthlyReserveEntry guarda itens de investimento.

Arquivos centrais: ReserveAccountController, ReserveAccountEntryController, MonthlyReserveController, MonthlyReserveEntryController, ReserveAccountService, MonthlyReserveService, seus repositories e models, além de DashboardService para os resumos financeiros calculados.

## Estrutura resumida dos models

| Model | Campos relevantes e invariantes |
|---|---|
| ReserveAccount | user_id, name e active. Nome não precisa ser único. Arquivar define active=false; não há exclusão pela interface. |
| ReserveAccountEntry | reserve_account_id, competency, balance e note. balance é o saldo declarado, não delta. Há uma declaração por conta e competência. |
| MonthlyReserve | user_id, competency, investimento e observations. Único por usuário e competência. |
| MonthlyReserveEntry | monthly_reserve_id, description e amount. O registro pai agrupa os itens da competência. |

## Endpoints

Todas as rotas exigem autenticação e limitam acesso ao usuário do token.

| Método e rota | Uso |
|---|---|
| GET /api/reserve-accounts | Lista contas ativas. Aceita include_archived; quando recebe competency, inclui current_balance, previous_balance, delta, is_inherited e note. |
| POST /api/reserve-accounts | Cria conta pelo name. |
| PUT/PATCH /api/reserve-accounts/{id} | Renomeia e/ou altera active. |
| GET /api/reserve-accounts/{id}/entries | Lista snapshots declarados, inclusive em conta arquivada. |
| PUT /api/reserve-accounts/{id}/entries/{YYYY-MM} | Cria ou atualiza saldo e nota de uma conta pertencente ao usuário na competência; a API não exige que ela esteja ativa. |
| DELETE /api/reserve-accounts/{id}/entries/{YYYY-MM} | Remove o snapshot; competências posteriores sem declaração própria voltam a herdar o snapshot válido anterior. |
| PUT /api/reserve-account-balances | Grava vários saldos para a mesma competência em uma transação. |
| GET/POST /api/monthly-reserves | Lista ou cria resumos mensais. |
| GET/PUT/PATCH/DELETE /api/monthly-reserves/{id} | Consulta, atualiza ou exclui um resumo mensal; o payload aceita investimento diretamente. |
| GET/POST /api/monthly-reserves/{id}/entries | Lista e cria itens de investimento da reserva. |
| PUT/PATCH/DELETE /api/monthly-reserves/{id}/entries/{entry} | Edita ou exclui um item de investimento da reserva. |

O payload do endpoint em lote tem competency e uma lista entries com account_id, balance e note opcional. Exige competência válida, pelo menos uma entrada, IDs de conta distintos pertencentes ao usuário e balance numérico não negativo. A API aceita contas arquivadas se forem do usuário; a interface envia apenas as contas ativas. Se uma conta não pertencer ao usuário, a operação falha sem manter gravações parciais. O evento de invalidação é enviado depois do commit.

A tela envia somente saldos alterados no lote. Renomeações são chamadas PUT separadas antes do lote; portanto, a atomicidade do endpoint em lote cobre os snapshots, não renomeações e saldos em conjunto.

## Herança e saldo exposto por conta

Para uma conta e competência X, current_balance usa o snapshot mais recente com competency menor ou igual a X. previous_balance usa o mesmo critério na competência imediatamente anterior. delta só é calculado quando ambos os saldos existem. is_inherited indica que o saldo atual veio de outra competência.

As rotas individuais de snapshot restringem o parâmetro ao formato YYYY-MM, mas não aplicam CompetencyRule para validar o número do mês. A listagem com filtro e o endpoint em lote aplicam a validação completa. Use competências válidas; este é um desvio da regra normativa observado no código atual.

O histórico de snapshots permanece acessível após arquivamento. include_archived permite que a tela de gerenciamento também apresente contas arquivadas e suas informações de saldo.

## Fórmulas do dashboard e da listagem

Para cada competência:

- remaining_amount = total_income - total_expense;
- previous_reserve = soma dos saldos vigentes das contas atualmente ativas;
- current_reserve = previous_reserve + remaining_amount;
- investment = MonthlyReserve.investimento da competência, ou zero sem registro;
- total_saved = current_reserve + investment.

DashboardService aplica a mesma agregação ao resumo mensal, analytics, evolução e listagem de MonthlyReserve. O campo previous_reserve é calculado; não é persistido e não significa necessariamente o fechamento real da competência anterior. Transações paid e pending são incluídas nos totais.

O estado active da conta é aplicado no momento da consulta. Uma conta arquivada fica fora dos agregados de todas as competências consultadas, inclusive as históricas. Isso não corresponde literalmente à confirmação atual da interface, que informa que ela deixa de entrar nos totais dos próximos meses. Mantenha essa diferença documentada ao alterar qualquer um dos fluxos.

## Investimentos

A interface mantém itens com descrição e valor; ao criar, editar ou excluir um item, MonthlyReserveService bloqueia a linha pai, executa a alteração e recalcula investimento pela soma dos itens dentro da mesma transação. O evento DadosFinanceirosAlterados é emitido depois do commit.

A API de MonthlyReserve também aceita investimento diretamente por compatibilidade. Essa gravação direta pode divergir da soma de MonthlyReserveEntry; a próxima mutação de item recalcula e substitui o valor agregado pela soma. O investimento continua separado de current_reserve e só é somado em total_saved.

## Integrações, invalidação e pontos de atenção

- O MonthlyReserveEditor consulta GET /api/reserve-accounts com competency e include_archived=1, e GET /api/dashboard/monthly-summary para atualizar a prévia do saldo da competência.
- Contas e transações alimentam o dashboard geral; itens e total mensal de investimento alimentam total_saved.
- Operações financeiras emitem DadosFinanceirosAlterados e avançam a versão do cache do dashboard.
- Snapshots não debitam nem creditam contas bancárias. Se o snapshot já incluir a sobra da competência, somar remaining_amount pode contar o dinheiro duas vezes; a regra atual não diferencia saldo de abertura de saldo de fechamento.
- Arquivar tem efeito retroativo nos agregados do dashboard porque contas inativas são filtradas independentemente da competência.
- O snapshot de saldo não é movimento de caixa. Não inferir transferências ou pagamentos a partir dele.
- Migração de dados: snapshots históricos do antigo campo reserva_anterior foram copiados para uma conta de reserva chamada Reserva por usuário antes da remoção da coluna. A migration correspondente é 2026_07_31_000003_backfill_reserve_accounts_from_monthly_reserves.php.


## Regras de negócio atualizadas — 2026-11

Esta seção substitui as fórmulas e os fluxos de investimento descritos acima.

- O módulo mantém um único fluxo de Reservas com vários itens identificados por ID, descrição e tipo (`reserve` ou `investment`). Os itens anteriores de `reserve_accounts` continuam como Reserva; novas classificações de Investimento usam a mesma estrutura de itens e snapshots.
- `reserve_account_entries.balance` é o saldo total do próprio item naquela competência, nunca um aporte. Sem uma declaração, usa-se o saldo declarado mais recente do mesmo item e a API informa `is_inherited`. Não se somam competências diferentes.
- `reserve_accounts.type` não pode ser alterado depois do cadastro. Reservas e Investimentos novos usam IDs e saldos da tabela `reserve_accounts`/`reserve_account_entries`; não usam nem compartilham os IDs dos registros antigos em `monthly_reserves` e `monthly_reserve_entries`.
- O histórico de `reserve_account_entries` é preservado. A tabela de status por competência registra a competência efetiva de arquivamento/reativação. Contas antigas com `active=false` recebem `archive_status_needs_review=true`, porque o sistema não conhece a data histórica. Até a revisão, seus saldos não entram nos totais e o dashboard sinaliza a pendência. A pessoa confirma a competência antes de o sistema aplicar o status ao histórico.
- A migration `2026_11_04_000003_archive_and_zero_legacy_investments` copia integralmente IDs de origem, usuário, competência, valores, observações/descrições, timestamps e relação pai dos registros antigos para `legacy_monthly_reserve_archives` e `legacy_monthly_reserve_entry_archives`, confere a cópia e só então zera `monthly_reserves.investimento` e `monthly_reserve_entries.amount`. `legacy_investment_migration_state` torna a operação idempotente. O rollback restaura os registros e valores a partir do arquivo.
- Os endpoints antigos de lançamentos de investimento permanecem disponíveis para leitura; escrita e exclusão retornam 422. O campo legado `investimento` aceita apenas zero. Investimentos novos são cadastrados como itens do tipo `investment`, com saldos por competência.
- Preparar/validar migration não autoriza executá-la no banco real. É necessária autorização explícita específica para a aplicação no banco real.