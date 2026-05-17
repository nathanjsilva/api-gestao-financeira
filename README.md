# API Gestao Financeira

API RESTful para controle financeiro pessoal multiusuario, com autenticacao via Sanctum, competencia mensal no formato `YYYY-MM` e endpoints de dashboard para consolidacao financeira.

## Stack

- PHP 8.3+
- Laravel 13
- MySQL 8
- Redis
- Laravel Sanctum
- Docker

## Objetivo do sistema

O sistema foi construido para controlar:

- usuarios
- categorias por usuario
- transacoes financeiras
- reservas mensais
- dashboard com indicadores e comparativos

Cada usuario acessa apenas os seus proprios dados.

## Principais regras de negocio

- a competencia mensal usa o formato `YYYY-MM`
- categorias pertencem ao usuario autenticado
- transacoes centralizam entradas e saidas
- nao existe parcelamento
- nao existe pagamento parcial
- nao existe vencimento
- exclusao e permanente
- recorrencia e controlada por `boolean`

## Arquitetura

O projeto segue arquitetura em camadas:

`Route -> Middleware -> FormRequest -> Controller -> Service -> Repository -> Model -> Resource`

### Camadas

- `Controller`: recebe a requisicao HTTP
- `Service`: aplica regra de negocio
- `Repository`: consulta e persiste dados
- `Request`: valida e normaliza entrada
- `Resource`: padroniza a saida JSON
- `Middleware`: trata responsabilidades transversais
- `Model`: representa as entidades do dominio

## Estrutura principal

```text
app/
|-- Http/
|   |-- Controllers/Api
|   |-- Middleware
|   |-- Requests
|   `-- Resources
|-- Models
|-- Providers
|-- Repositories
|-- Rules
`-- Services
```

## Modulos implementados

- autenticacao
- categorias
- transacoes
- reservas mensais
- dashboard

## Ambiente com Docker

O projeto foi preparado para rodar com:

- `gestao_app`
- `gestao_db`
- `gestao_redis`

### Subir os containers

```bash
docker compose up -d
```

### Instalar dependencias no container

```bash
docker compose exec app composer install
```

### Rodar migrations

```bash
docker compose exec app php artisan migrate --force
```

### Listar rotas

```bash
docker compose exec app php artisan route:list
```

## Configuracao de ambiente

Exemplo de variaveis importantes:

```env
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=gestao_financeira
DB_USERNAME=gestao
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379

SANCTUM_STATEFUL_DOMAINS=localhost:5173
SANCTUM_TOKEN_EXPIRATION=1440
```

## Autenticacao

A API utiliza Sanctum com Bearer Token.

Header esperado nas rotas protegidas:

```http
Authorization: Bearer SEU_TOKEN
Accept: application/json
Content-Type: application/json
```

### Fluxo recomendado

1. registrar usuario em `/api/auth/register`
2. armazenar o token retornado
3. enviar o token nas rotas protegidas
4. fazer logout em `/api/auth/logout` quando necessario

## Endpoints principais

### Auth

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`

### Categories

- `GET /api/categories`
- `GET /api/categories/{id}`
- `POST /api/categories`
- `PUT/PATCH /api/categories/{id}`
- `DELETE /api/categories/{id}`

### Transactions

- `GET /api/transactions?competency=2026-05`
- `GET /api/transactions/{id}`
- `POST /api/transactions`
- `PUT/PATCH /api/transactions/{id}`
- `DELETE /api/transactions/{id}`

### Monthly Reserves

- `GET /api/monthly-reserves`
- `GET /api/monthly-reserves/{id}`
- `POST /api/monthly-reserves`
- `PUT/PATCH /api/monthly-reserves/{id}`
- `DELETE /api/monthly-reserves/{id}`

### Dashboard

- `GET /api/dashboard/monthly-summary?competency=2026-05`
- `GET /api/dashboard/category-comparison?current_competency=2026-05&previous_competency=2026-04`
- `GET /api/dashboard/monthly-evolution?start_competency=2026-01&end_competency=2026-05`
- `GET /api/dashboard/month-comparison?first_competency=2026-04&second_competency=2026-05`

## Indicadores do dashboard

O dashboard foi preparado para retornar:

- total de entradas do mes
- total de gastos do mes
- valor restante
- reserva atual
- total guardado
- categoria com maior gasto
- categoria com menor gasto
- gastos por categoria
- comparativo de categorias entre meses
- evolucao mensal
- comparacao entre meses

## Formulas utilizadas

```text
valor_restante = total_entradas - total_gastos
reserva_atual = reserva_anterior + valor_restante
total_guardado = reserva_atual + investimento
```

## Seguranca aplicada

- autenticacao com Sanctum
- ownership por usuario nas consultas
- middleware para forcar JSON
- throttle em login e registro
- expiracao configurada para tokens
- validacoes contextuais por usuario

## Validacoes implementadas

- `competency` com regra customizada
- email normalizado em auth
- nome e descricao com `trim`
- unicidade de categoria por usuario e tipo
- unicidade de reserva mensal por usuario e competencia
- categoria da transacao restrita ao usuario autenticado
- consistencia entre `category.type` e `transaction.type`

## Otimizacoes aplicadas

- eager loading com colunas especificas
- agregacoes do dashboard nos repositories
- consultas em lote para evolucao mensal e comparacoes
- indices de banco voltados para `user_id`, `competency`, `type` e `status`

## Documentacao local complementar

Existem dois arquivos locais de apoio que nao sobem para o GitHub por estarem no `.gitignore`:

- `local-docs/USO_API_LOCAL.md`
- `local-docs/EXPLICACAO_SISTEMA_LOCAL.md`

Eles foram criados para uso local e estudo do projeto.

## Comandos uteis

### Rodar migrations

```bash
docker compose exec app php artisan migrate --force
```

### Limpar cache de configuracao

```bash
docker compose exec app php artisan config:clear
```

### Rodar testes

```bash
docker compose exec app php artisan test
```

### Abrir shell no container da aplicacao

```bash
docker compose exec app sh
```

## Melhorias futuras

- testes automatizados
- policies
- handler global para excecoes de dominio
- cache no dashboard
- paginacao e filtros adicionais
- versionamento de API

## Licenca

Este projeto utiliza a licenca MIT.
