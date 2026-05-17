# Explicacao detalhada do sistema

## Objetivo do sistema

Este projeto implementa uma API RESTful de controle financeiro pessoal multiusuario com Laravel, usando:

- Laravel
- MySQL
- Sanctum
- Controllers
- Services
- Repositories
- Form Requests
- Resources
- Middleware
- Models

O foco e separar responsabilidade por camada e manter o codigo simples, escalavel e com baixo acoplamento.

## Visao geral da arquitetura

Fluxo principal:

`Route -> Middleware -> FormRequest -> Controller -> Service -> Repository -> Model -> Resource`

### Controller

Recebe a requisicao HTTP, chama o service e devolve JSON/Resource.

### Service

Concentra regra de negocio.

### Repository

Concentra acesso e consulta ao banco.

### Request

Valida e normaliza entrada HTTP.

### Resource

Define formato de saida da API.

### Middleware

Trata preocupacoes transversais como JSON forcado, throttle e autenticacao.

### Model

Representa entidades do dominio e seus relacionamentos.

## Etapa por etapa

### Etapa 1: Arquitetura do projeto

Foi definida uma arquitetura em camadas para evitar controller gordo e regra espalhada.

### Etapa 2: Estrutura de pastas

Foram organizadas as pastas de forma compatível com Laravel e com separacao por responsabilidade:

- `app/Http/Controllers/Api`
- `app/Http/Requests`
- `app/Http/Resources`
- `app/Services`
- `app/Repositories`
- `app/Models`

### Etapa 3: Modelagem do banco

Entidades definidas:

- `users`
- `categories`
- `transactions`
- `monthly_reserves`
- `personal_access_tokens`

### Etapa 4: Relacionamentos

- User `hasMany` Category
- User `hasMany` Transaction
- User `hasMany` MonthlyReserve
- Category `belongsTo` User
- Category `hasMany` Transaction
- Transaction `belongsTo` User
- Transaction `belongsTo` Category
- MonthlyReserve `belongsTo` User

### Etapa 5: Migrations

Foram criadas migrations com:

- FKs
- indices
- unicidade por usuario
- `competency` como `char(7)`
- `amount`, `reserva_anterior`, `investimento` em decimal

### Etapa 6: Models

Foram criados:

- `User`
- `Category`
- `Transaction`
- `MonthlyReserve`

Cada model recebeu:

- `fillable`
- `casts`
- relacionamentos

### Etapa 7: Repositories

Foram criados repositories concretos, sem interface, para evitar overengineering inicial.

### Etapa 8: Services

Os services passaram a centralizar:

- cadastro/login
- ownership
- regra de categoria da transacao
- unicidade de reserva mensal
- formulas do dashboard

### Etapa 9: Form Requests

Validacoes de entrada foram movidas para requests dedicados.

### Etapa 10: Resources

A API passou a ter contrato de saida controlado.

### Etapa 11: Controllers

Controllers CRUD e de dashboard foram criados com responsabilidade enxuta.

### Etapa 12: Rotas

Rotas publicas e protegidas foram organizadas em `routes/api.php`.

### Etapa 13: Middlewares

Foi criado middleware para forcar JSON e configurado o pipeline da API.

### Etapa 14: Sanctum

Foi instalada autenticacao por token com:

- migration de tokens
- `HasApiTokens` no `User`
- login
- register
- logout

### Etapa 15: Dashboard

Foram criados endpoints analiticos:

- resumo mensal
- comparacao por categoria
- evolucao mensal
- comparacao entre meses

### Etapa 16: Otimizacao de queries

O dashboard foi ajustado para reduzir consultas repetidas em comparacoes e intervalos.

### Etapa 17: Boas praticas

Foi reduzida repeticao nos controllers com `usuarioIdAutenticado()`.

### Etapa 18: Seguranca

Foram adicionados:

- throttles de auth
- expiracao de token
- protecao de rotas com Sanctum

### Etapa 19: Validacoes

Foram criadas validacoes mais inteligentes, inclusive regra customizada para competencia.

### Etapa 20: Melhorias futuras

Foi definido o caminho para:

- tests
- policies
- handler global
- cache

## Explicacao por camada e por arquivo

## Controllers

### `app/Http/Controllers/Controller.php`

#### `usuarioIdAutenticado(): int`

Retorna o id do usuario autenticado. Existe para evitar repetir `auth()->id()` em varios controllers.

### `app/Http/Controllers/Api/AuthController.php`

#### `register(RegisterRequest $request): JsonResponse`

- recebe dados validados de cadastro
- chama `AuthService::cadastrar`
- gera token com Sanctum
- devolve usuario e token

#### `login(LoginRequest $request): JsonResponse`

- valida email e senha
- chama `AuthService::validarCredenciais`
- se falhar retorna `401`
- se passar gera token e devolve os dados

#### `logout(Request $request): JsonResponse`

- pega usuario autenticado
- revoga token atual com `AuthService::revogarTokenAtual`

### `CategoryController`

#### `index()`

Lista categorias do usuario autenticado.

#### `show(int $id)`

Busca categoria por id respeitando ownership.

#### `store(StoreCategoryRequest $request)`

Cria categoria com dados validados.

#### `update(UpdateCategoryRequest $request, int $id)`

Atualiza categoria do usuario.

#### `destroy(int $id)`

Exclui categoria do usuario.

### `TransactionController`

#### `index(ListTransactionRequest $request)`

Lista transacoes de uma competencia.

#### `show(int $id)`

Busca transacao por id.

#### `store(StoreTransactionRequest $request)`

Cria transacao apos validacao de request e regra de negocio.

#### `update(UpdateTransactionRequest $request, int $id)`

Atualiza transacao. A regra de consistencia entre categoria e tipo continua no service.

#### `destroy(int $id)`

Exclui transacao.

### `MonthlyReserveController`

#### `index()`

Lista reservas mensais do usuario.

#### `show(int $id)`

Busca reserva mensal por id.

#### `store(StoreMonthlyReserveRequest $request)`

Cria reserva mensal.

#### `update(UpdateMonthlyReserveRequest $request, int $id)`

Atualiza reserva mensal.

#### `destroy(int $id)`

Exclui reserva mensal.

### `DashboardController`

#### `resumoMensal(MonthlySummaryRequest $request)`

Devolve consolidado do mes.

#### `comparativoCategorias(CategoryComparisonRequest $request)`

Compara categorias entre dois meses.

#### `evolucaoMensal(MonthlyEvolutionRequest $request)`

Retorna evolucao entre competencias.

#### `comparacaoEntreMeses(MonthComparisonRequest $request)`

Compara indicadores consolidados entre dois meses.

## Services

### `AuthService`

#### `cadastrar(array $dados): User`

- verifica email existente
- cria usuario

#### `validarCredenciais(string $email, string $senha): ?User`

- busca usuario por email
- compara senha com `Hash::check`
- retorna usuario ou `null`

#### `gerarToken(User $usuario, string $nomeToken): string`

Gera token Sanctum e devolve `plainTextToken`.

#### `revogarTokenAtual(User $usuario): void`

Revoga apenas o token usado na requisicao atual.

### `CategoryService`

#### `listar(int $usuarioId): Collection`

Lista categorias do usuario.

#### `buscarPorId(int $id, int $usuarioId): ?Category`

Busca categoria por id e usuario.

#### `criar(int $usuarioId, array $dados): Category`

Cria categoria vinculada ao usuario autenticado.

#### `atualizar(int $id, int $usuarioId, array $dados): Category`

Busca categoria, atualiza e faz refresh.

#### `excluir(int $id, int $usuarioId): void`

Exclui categoria apos validar ownership.

#### `buscarCategoriaOuFalhar(...)`

Metodo interno para concentrar a regra de "nao encontrada".

### `TransactionService`

#### `listar(int $usuarioId, string $competencia): Collection`

Lista transacoes do mes.

#### `buscarPorId(int $id, int $usuarioId): ?Transaction`

Busca transacao por ownership.

#### `criar(int $usuarioId, array $dados): Transaction`

- valida se a categoria pertence ao usuario
- valida se o tipo da categoria bate com a transacao
- cria a transacao

#### `atualizar(int $id, int $usuarioId, array $dados): Transaction`

- busca transacao
- calcula tipo/categoria final
- revalida consistencia
- atualiza e recarrega relacao

#### `excluir(int $id, int $usuarioId): void`

Exclui transacao do usuario.

#### `buscarTransacaoOuFalhar(...)`

Metodo interno para falha controlada de ownership.

#### `validarCategoriaDaTransacao(...)`

Garante que a categoria exista para o usuario e seja do mesmo tipo da transacao.

### `MonthlyReserveService`

#### `listar(int $usuarioId): Collection`

Lista reservas do usuario.

#### `buscarPorCompetencia(int $usuarioId, string $competencia): ?MonthlyReserve`

Busca reserva por competencia.

#### `buscarPorId(int $id, int $usuarioId): ?MonthlyReserve`

Busca reserva por id.

#### `criar(int $usuarioId, array $dados): MonthlyReserve`

Impede duplicidade de competencia por usuario e cria reserva.

#### `atualizar(int $id, int $usuarioId, array $dados): MonthlyReserve`

Atualiza reserva e revalida competencia se ela mudar.

#### `excluir(int $id, int $usuarioId): void`

Exclui reserva.

#### `buscarPorIdOuFalhar(...)`

Metodo interno de ownership.

### `DashboardService`

#### `obterResumoMensal(int $usuarioId, string $competencia): array`

- monta resumo base do mes
- busca gastos por categoria
- calcula maior e menor categoria de gasto

#### `obterComparativoDeCategorias(...)`

Compara categoria a categoria entre dois meses.

#### `obterEvolucaoMensal(...)`

- gera todas as competencias do intervalo
- monta resumo para cada mes

#### `obterComparacaoEntreMeses(...)`

Compara dois meses consolidados e calcula diferencas.

#### `obterResumosBaseIndexados(...)`

Metodo interno de otimizacao:

- busca varios resumos em lote
- busca reservas em lote
- indexa tudo por competencia

#### `gerarCompetenciasEntrePeriodos(...)`

Gera a sequencia `YYYY-MM` entre dois meses e valida a ordem do periodo.

## Repositories

### `UserRepository`

#### `buscarPorEmail(string $email): ?User`

Busca usuario por email.

#### `criar(array $data): User`

Cria usuario.

### `CategoryRepository`

#### `listarPorUsuarioId(int $userId): Collection`

Lista categorias ordenadas por tipo e nome.

#### `buscarPorIdEUsuarioId(...)`

Busca categoria por ownership.

#### `buscarPorIdTipoEUsuarioId(...)`

Busca categoria por ownership e tipo.

#### `criar`, `atualizar`, `excluir`

Persistencia simples da entidade.

### `TransactionRepository`

#### `listarPorUsuarioIdECompetencia(...)`

Lista transacoes do mes com eager loading da categoria.

#### `buscarPorIdEUsuarioId(...)`

Busca transacao por ownership.

#### `criar`, `atualizar`, `excluir`

Persistencia.

#### `obterResumoMensalPorTipo(...)`

Agrupa valores por tipo no mes.

#### `obterResumoMensalPorTipoEntreCompetencias(...)`

Versao otimizada para varias competencias.

#### `obterTotaisDeGastosAgrupadosPorCategoria(...)`

Usado no dashboard mensal.

#### `obterTotaisDeGastosPorCategoriaEntreCompetencias(...)`

Usado na comparacao entre categorias.

### `MonthlyReserveRepository`

#### `listarPorUsuarioId(...)`

Lista reservas do usuario.

#### `buscarPorUsuarioIdECompetencia(...)`

Busca reserva por competencia.

#### `obterPorUsuarioIdECompetencias(...)`

Busca varias reservas em lote para o dashboard.

#### `buscarPorIdEUsuarioId(...)`

Busca reserva por ownership.

#### `criar`, `atualizar`, `excluir`

Persistencia.

## Models

### `User`

- representa o usuario autenticavel
- usa `HasApiTokens`
- possui relacoes com categorias, transacoes e reservas

### `Category`

- representa categoria do usuario
- possui relacao com `User`
- possui relacao com `Transaction`

### `Transaction`

- representa entrada ou saida
- possui cast decimal e boolean
- relaciona com `User` e `Category`

### `MonthlyReserve`

- representa a reserva mensal
- possui cast decimal
- relaciona com `User`

## Requests

### Auth Requests

- normalizam email e nome
- validam obrigatoriedade e unicidade

### Category Requests

- fazem trim do nome
- validam unicidade por usuario e tipo

### Transaction Requests

- fazem trim da descricao
- validam categoria do proprio usuario
- usam `CompetencyRule`

### MonthlyReserve Requests

- usam `CompetencyRule`
- validam unicidade de competencia por usuario

### Dashboard Requests

- isolam filtros de consulta
- tiram validacao inline do controller

## Rule customizada

### `CompetencyRule`

Valida:

- formato `YYYY-MM`
- mes valido com `checkdate`

Isso evita aceitar valores como `2026-13`.

## Resources

### `CategoryResource`

Define payload de saida de categoria.

### `TransactionResource`

Define payload de saida de transacao e categoria associada.

### `MonthlyReserveResource`

Define payload da reserva mensal.

### `DashboardResource`

Define payload do resumo mensal.

### `DashboardCategoryComparisonResource`

Define payload da comparacao por categoria.

### `DashboardMonthlyEvolutionResource`

Define payload da evolucao mensal.

### `DashboardMonthComparisonResource`

Define payload da comparacao entre meses.

## Middleware

### `ForceJsonResponse`

Forca `Accept: application/json` nas rotas da API.

Motivo:

- evitar resposta HTML inesperada
- manter comportamento consistente

## Provider

### `AppServiceProvider`

#### `boot()`

Configura rate limiter:

- `auth`
- `register`

Isso protege login e cadastro contra abuso.

## Bootstrap

### `bootstrap/app.php`

Pontos principais:

- registra `routes/api.php`
- liga `statefulApi()` para Sanctum
- liga `throttleApi()`
- aplica `ForceJsonResponse`
- desativa redirecionamento HTML de guest

## Rotas

### `routes/api.php`

Rotas publicas:

- `POST /auth/register`
- `POST /auth/login`

Rotas protegidas:

- `POST /auth/logout`
- CRUD de `categories`
- CRUD de `transactions`
- CRUD de `monthly-reserves`
- endpoints de dashboard

## Banco de dados

### `categories`

- `user_id`
- `name`
- `type`

### `transactions`

- `user_id`
- `category_id`
- `description`
- `amount`
- `type`
- `status`
- `competency`
- `is_recurring`

### `monthly_reserves`

- `user_id`
- `competency`
- `reserva_anterior`
- `investimento`
- `observations`

### `personal_access_tokens`

Tabela do Sanctum para autenticacao por token.

## Decisoes tecnicas importantes

- `competency` foi modelada como `YYYY-MM` e nao como data completa
- `type` existe em `category` e `transaction` para facilitar analise
- ownership e protegido no repository e reforcado no service
- validacao de borda fica nos requests
- regra de negocio fica no service
- query e agregacao ficam no repository
- saida da API fica nos resources

## Como ler o projeto

Se quiser entender uma funcionalidade do inicio ao fim, siga esta ordem:

1. rota
2. controller
3. request
4. service
5. repository
6. model
7. resource

Esse caminho mostra exatamente como a requisicao caminha dentro do sistema.
