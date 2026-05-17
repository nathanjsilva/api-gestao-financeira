# API Gestão Financeira

API RESTful para controle financeiro pessoal multiusuário, com autenticação via Sanctum, competência mensal no formato `YYYY-MM` e endpoints de dashboard para consolidação financeira.

## Stack

- PHP 8.3+
- Laravel 13
- MySQL 8
- Redis
- Laravel Sanctum
- Docker

## Objetivo do sistema

O sistema foi construído para controlar:

- usuários
- categorias por usuário
- transações financeiras
- reservas mensais
- dashboard com indicadores e comparativos

Cada usuário acessa apenas os seus próprios dados.

## Principais regras de negócio

- a competência mensal usa o formato `YYYY-MM`
- categorias pertencem ao usuário autenticado
- transações centralizam entradas e saídas
- não existe parcelamento
- não existe pagamento parcial
- não existe vencimento
- exclusão é permanente
- recorrência é controlada por `boolean`

## Arquitetura

O projeto segue arquitetura em camadas:

`Route -> Middleware -> FormRequest -> Controller -> Service -> Repository -> Model -> Resource`

### Camadas

- `Controller`: recebe a requisição HTTP
- `Service`: aplica regra de negócio
- `Repository`: consulta e persiste dados
- `Request`: valida e normaliza entrada
- `Resource`: padroniza a saída JSON
- `Middleware`: trata responsabilidades transversais
- `Model`: representa as entidades do domínio

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

## Módulos implementados

- autenticação
- categorias
- transações
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

### Instalar dependências no container

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

## Configuração de ambiente

Exemplo de variáveis importantes:

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

## Autenticação

A API utiliza Sanctum com Bearer Token.

Header esperado nas rotas protegidas:

```http
Authorization: Bearer SEU_TOKEN
Accept: application/json
Content-Type: application/json
```

### Fluxo recomendado

1. registrar usuário em `/api/auth/register`
2. armazenar o token retornado
3. enviar o token nas rotas protegidas
4. fazer logout em `/api/auth/logout` quando necessário

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

- total de entradas do mês
- total de gastos do mês
- valor restante
- reserva atual
- total guardado
- categoria com maior gasto
- categoria com menor gasto
- gastos por categoria
- comparativo de categorias entre meses
- evolução mensal
- comparação entre meses

## Fórmulas utilizadas

```text
valor_restante = total_entradas - total_gastos
reserva_atual = reserva_anterior + valor_restante
total_guardado = reserva_atual + investimento
```

## Segurança aplicada

- autenticação com Sanctum
- ownership por usuário nas consultas
- middleware para forçar JSON
- throttle em login e registro
- expiração configurada para tokens
- validações contextuais por usuário

## Validações implementadas

- `competency` com regra customizada
- email normalizado em auth
- nome e descrição com `trim`
- unicidade de categoria por usuário e tipo
- unicidade de reserva mensal por usuário e competência
- categoria da transação restrita ao usuário autenticado
- consistência entre `category.type` e `transaction.type`

## Otimizações aplicadas

- eager loading com colunas específicas
- agregações do dashboard nos repositories
- consultas em lote para evolução mensal e comparações
- índices de banco voltados para `user_id`, `competency`, `type` e `status`

## Documentação local complementar

Existem dois arquivos locais de apoio que não sobem para o GitHub por estarem no `.gitignore`:

- `local-docs/USO_API_LOCAL.md`
- `local-docs/EXPLICACAO_SISTEMA_LOCAL.md`

Eles foram criados para uso local e estudo do projeto.

## Comandos úteis

### Rodar migrations

```bash
docker compose exec app php artisan migrate --force
```

### Limpar cache de configuração

```bash
docker compose exec app php artisan config:clear
```

### Rodar testes

```bash
docker compose exec app php artisan test
```

### Abrir shell no container da aplicação

```bash
docker compose exec app sh
```

## Melhorias futuras

- testes automatizados
- policies
- handler global para exceções de domínio
- cache no dashboard
- paginação e filtros adicionais
- versionamento de API

## Licença

Este projeto utiliza a licença MIT.
