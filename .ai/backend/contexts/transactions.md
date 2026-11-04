# Contexto: transações financeiras

## Responsabilidade e dependências

CRUD de transações do usuário por competência mensal. Cada transação depende de uma categoria geral do mesmo usuário e do mesmo tipo. O dashboard geral soma as transações; o módulo de cartões é independente.

## Estrutura principal

Transaction contém user_id, category_id, description, amount, type, status, competency e is_recurring. Os valores de domínio são:

- type: income ou expense;
- status: paid ou pending;
- competency: YYYY-MM;
- amount: pelo menos 0.01.

O índice da consulta mensal é por usuário e competência. Consulte a migration para detalhes de tipos e constraints.

## Endpoints

Todas as rotas exigem auth:sanctum.

| Método | Caminho | Comportamento |
|---|---|---|
| GET | /api/transactions?competency=YYYY-MM | Lista a competência; o filtro é obrigatório. |
| POST | /api/transactions | Cria uma transação. |
| GET | /api/transactions/{id} | Busca registro próprio. |
| PUT/PATCH | /api/transactions/{id} | Atualização parcial. |
| DELETE | /api/transactions/{id} | Exclui registro próprio. |

## Fluxo de criação

~~~text
Formulário
→ StoreTransactionRequest valida campos e existência da categoria do usuário
→ TransactionController obtém a identidade autenticada e dados validados
→ TransactionService confirma ownership e compatibilidade do tipo da categoria
→ TransactionRepository persiste com user_id autenticado e carrega category
→ emite DadosFinanceirosAlterados
→ TransactionResource retorna a resposta
~~~

A validação de existência no Request não garante compatibilidade do tipo; essa regra é verificada pelo Service.

## Fluxo de atualização

UpdateTransactionRequest aceita campos parciais. O Service busca a transação por id e usuário, combina type/category_id enviados com os valores atuais, verifica a categoria final e atualiza o registro. Recurso de outro usuário é tratado como inexistente.

## Validações

Store exige category_id próprio, description até 255 caracteres, amount numérico mínimo 0.01, type em income/expense, status em paid/pending, competency validada por CompetencyRule e is_recurring booleano obrigatório.

Update permite campos parciais. Se categoria ou tipo não forem enviados, o Service usa os valores existentes para validar a combinação final.

## Regras e integração

1. O usuário só consulta, altera ou exclui suas próprias transações.
2. category_id deve pertencer ao usuário autenticado e ter o mesmo type da transação.
3. A listagem sempre exige competency; não existe endpoint para listar todos os meses de uma vez.
4. is_recurring é marcador informativo e não gera transações futuras.
5. O dashboard inclui transações paid e pending, pois não há data de liquidação.
6. Criação, atualização e exclusão emitem DadosFinanceirosAlterados e avançam a versão do cache geral.
7. Categorias financeiras não emitem esse evento ao serem alteradas; nomes em analytics cacheados podem demorar até cinco minutos para atualizar.

## Integração com analytics

DashboardService agrega receitas/despesas por competência e tipo. FinancialAnalyticsService usa as agregações para evolução, fluxo, comparações, ranking de despesas, taxas e insights. Os métodos de agregação ficam em TransactionRepository.

## Referências

- Controller: app/Http/Controllers/Api/TransactionController.php
- Service: app/Services/TransactionService.php
- Repository: app/Repositories/TransactionRepository.php
- Requests: app/Http/Requests/Transaction/
- Resource: app/Http/Resources/TransactionResource.php
- Validação de competência: app/Rules/CompetencyRule.php