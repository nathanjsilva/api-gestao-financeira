# Visão do sistema financeiro

## Escopo

Este arquivo é um resumo local da implementação. As regras normativas estão em AGENTS.md; para detalhes atualizados, consulte README.md, docs/fluxograma-arquitetura-aplicacao.md e os contextos em .ai/.

## Componentes

- Backend: Laravel 13, Sanctum, Eloquent e API JSON em /api.
- Frontend: SPA Vue 3 com TypeScript em frontend/.
- Persistência: banco configurado pelo ambiente; Docker Compose fornece MySQL 8.
- Cache: Laravel configurável. O Compose fornece Redis, mas config/cache.php usa database como padrão.
- Docker Compose sobe app, db e redis; a SPA é executada separadamente pelo Vite.

A rota web / retorna a view welcome do Laravel. Ela não publica a SPA.

## Arquitetura da API

~~~text
routes/api.php
→ middleware da API e auth:sanctum nas rotas privadas
→ Form Request
→ Controller
→ Service
→ Repository
→ Model Eloquent
→ banco

Controller → Resource ou resposta JSON direta
~~~

O cliente Vue chama a API por services de módulo e pelo httpClient compartilhado. O ID do usuário é obtido da autenticação no backend. Não envie nem use user_id do cliente como autoridade.

## Módulos

- Autenticação: cadastro, login, logout e tokens pessoais Sanctum.
- Categorias gerais: categorias income/expense para transações.
- Transações: registros por competência; paid e pending são ambos incluídos nos agregados.
- Reserva: contas nomeadas, snapshots de saldo por competência, reserva mensal e itens de investimento.
- Dashboard geral: transações, contas de reserva ativas e investimento mensal.
- Cartões: cartões, categorias próprias, compras e parcelas previstas.
- Dashboard de cartões: agrega parcelas e mantém cache independente do dashboard geral.

Categorias de cartão não são categorias gerais. Compras de cartão não são integradas automaticamente a transações.

## Fórmulas implementadas

~~~text
saldo restante = total de receitas - total de despesas
reserva base = soma dos snapshots vigentes das contas atualmente ativas
reserva atual = reserva base + saldo restante
total guardado = reserva atual + investimento mensal
~~~

O snapshot é um saldo declarado, não uma movimentação. Sem declaração na competência consultada, herda-se a declaração anterior. O status ativo atual da conta afeta também as consultas históricas.

Itens de investimento somam o campo investimento quando criados, editados ou excluídos. A API permite gravar o total diretamente, portanto pode haver divergência até a próxima mutação de item.

Compras de cartão geram parcelas em centavos inteiros; os centavos restantes vão para as parcelas finais. Os dashboards de cartões somam parcelas, e não o valor total do cabeçalho.

## Fonte de verdade do comportamento

- Rotas: routes/api.php e routes/web.php.
- Banco: database/migrations.
- Backend: app/Http, app/Services, app/Repositories e app/Models.
- Frontend: frontend/src/pages, services, router e stores.
- Regras universais: AGENTS.md.
- Diagramas e contratos: docs/fluxograma-arquitetura-aplicacao.md.