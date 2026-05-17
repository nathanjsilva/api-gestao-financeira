# Guia de Uso da API

## Objetivo

Este arquivo documenta como consumir a API de controle financeiro pessoal, quais endpoints existem, quais dados enviar e quais respostas esperar.

## Base da API

- URL base local via Docker: `http://localhost:8000/api`
- Autenticacao: Bearer Token com Sanctum
- Header obrigatorio para rotas protegidas:

```http
Authorization: Bearer SEU_TOKEN
Accept: application/json
Content-Type: application/json
```

## Regras gerais

- Todas as rotas protegidas exigem token valido.
- Todos os dados sao isolados por usuario autenticado.
- A competencia mensal sempre usa o formato `YYYY-MM`.
- Tipos aceitos:
  - `income`
  - `expense`
- Status aceitos:
  - `paid`
  - `pending`

## Respostas padrao

### 200 OK

Usado em consultas e atualizacoes bem-sucedidas.

### 201 Created

Usado em criacao bem-sucedida.

### 204 No Content

Usado em exclusao bem-sucedida.

### 401 Unauthorized

Quando o token nao foi enviado, esta invalido ou expirou.

Exemplo:

```json
{
  "message": "Unauthenticated."
}
```

### 404 Not Found

Quando o recurso nao pertence ao usuario ou nao existe.

Exemplo:

```json
{
  "message": "Categoria nao encontrada."
}
```

### 422 Unprocessable Entity

Quando existe erro de validacao ou regra de negocio.

Exemplo de validacao:

```json
{
  "message": "The competency field is required.",
  "errors": {
    "competency": [
      "The competency field is required."
    ]
  }
}
```

Exemplo de regra de negocio:

```json
{
  "message": "A categoria informada nao pertence ao usuario ou nao corresponde ao tipo da transacao."
}
```

### 429 Too Many Requests

Quando o limite de tentativas de autenticacao e excedido.

## Autenticacao

### POST `/auth/register`

Cria usuario e ja devolve token.

Body:

```json
{
  "name": "Nathan",
  "email": "nathan@email.com",
  "password": "12345678",
  "password_confirmation": "12345678"
}
```

Retorno 201:

```json
{
  "message": "Usuario cadastrado com sucesso.",
  "data": {
    "id": 1,
    "name": "Nathan",
    "email": "nathan@email.com",
    "token": "1|token..."
  }
}
```

Possiveis retornos:

- `201` usuario criado
- `422` email duplicado
- `422` erro de validacao
- `429` excesso de tentativas

### POST `/auth/login`

Autentica usuario e devolve token.

Body:

```json
{
  "email": "nathan@email.com",
  "password": "12345678"
}
```

Retorno 200:

```json
{
  "message": "Login realizado com sucesso.",
  "data": {
    "id": 1,
    "name": "Nathan",
    "email": "nathan@email.com",
    "token": "2|token..."
  }
}
```

Possiveis retornos:

- `200` login realizado
- `401` credenciais invalidas
- `422` erro de validacao
- `429` excesso de tentativas

### POST `/auth/logout`

Revoga o token atual.

Header:

```http
Authorization: Bearer SEU_TOKEN
```

Retorno 200:

```json
{
  "message": "Logout realizado com sucesso."
}
```

Possiveis retornos:

- `200` logout realizado
- `401` token ausente/invalido/expirado

## Categorias

### GET `/categories`

Lista categorias do usuario autenticado.

Retorno 200:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Salario",
      "type": "income",
      "created_at": "2026-05-17T12:00:00.000000Z",
      "updated_at": "2026-05-17T12:00:00.000000Z"
    }
  ]
}
```

### GET `/categories/{id}`

Busca uma categoria.

Possiveis retornos:

- `200` categoria encontrada
- `404` categoria nao encontrada

### POST `/categories`

Cria categoria.

Body:

```json
{
  "name": "Mercado",
  "type": "expense"
}
```

Retorno 201:

```json
{
  "data": {
    "id": 2,
    "name": "Mercado",
    "type": "expense",
    "created_at": "2026-05-17T12:10:00.000000Z",
    "updated_at": "2026-05-17T12:10:00.000000Z"
  }
}
```

Possiveis retornos:

- `201` criada
- `422` nome duplicado para o mesmo usuario e tipo
- `422` validacao

### PUT/PATCH `/categories/{id}`

Atualiza categoria.

Body parcial:

```json
{
  "name": "Mercado Mensal"
}
```

Possiveis retornos:

- `200` atualizada
- `404` categoria nao encontrada
- `422` validacao de unicidade

### DELETE `/categories/{id}`

Exclui categoria.

Possiveis retornos:

- `204` excluida
- `404` categoria nao encontrada

Observacao:

- se houver restricao de banco por transacoes vinculadas, a exclusao pode falhar em fluxo futuro se for tratada globalmente

## Transacoes

### GET `/transactions?competency=2026-05`

Lista transacoes da competencia.

Retorno 200:

```json
{
  "data": [
    {
      "id": 1,
      "category_id": 2,
      "category": {
        "id": 2,
        "name": "Mercado",
        "type": "expense",
        "created_at": null,
        "updated_at": null
      },
      "description": "Compra do mes",
      "amount": "350.00",
      "type": "expense",
      "status": "paid",
      "competency": "2026-05",
      "is_recurring": false,
      "created_at": "2026-05-17T12:20:00.000000Z",
      "updated_at": "2026-05-17T12:20:00.000000Z"
    }
  ]
}
```

Possiveis retornos:

- `200` lista retornada
- `422` competencia invalida

### GET `/transactions/{id}`

Busca uma transacao.

Possiveis retornos:

- `200` encontrada
- `404` nao encontrada

### POST `/transactions`

Cria transacao.

Body:

```json
{
  "category_id": 2,
  "description": "Compra do mes",
  "amount": 350.00,
  "type": "expense",
  "status": "paid",
  "competency": "2026-05",
  "is_recurring": false
}
```

Possiveis retornos:

- `201` criada
- `422` categoria nao pertence ao usuario
- `422` tipo da categoria nao bate com o tipo da transacao
- `422` competencia invalida
- `422` validacao

### PUT/PATCH `/transactions/{id}`

Atualiza transacao.

Body parcial:

```json
{
  "status": "pending",
  "description": "Compra ajustada"
}
```

Possiveis retornos:

- `200` atualizada
- `404` transacao nao encontrada
- `422` categoria invalida para o usuario/tipo
- `422` validacao

### DELETE `/transactions/{id}`

Exclui transacao.

Possiveis retornos:

- `204` excluida
- `404` nao encontrada

## Reservas mensais

### GET `/monthly-reserves`

Lista reservas mensais do usuario.

### GET `/monthly-reserves/{id}`

Busca reserva mensal por id.

Possiveis retornos:

- `200` encontrada
- `404` nao encontrada

### POST `/monthly-reserves`

Cria reserva mensal.

Body:

```json
{
  "competency": "2026-05",
  "reserva_anterior": 1000.00,
  "investimento": 300.00,
  "observations": "Reserva do mes"
}
```

Retorno 201:

```json
{
  "data": {
    "id": 1,
    "competency": "2026-05",
    "reserva_anterior": "1000.00",
    "investimento": "300.00",
    "observations": "Reserva do mes",
    "created_at": "2026-05-17T12:30:00.000000Z",
    "updated_at": "2026-05-17T12:30:00.000000Z"
  }
}
```

Possiveis retornos:

- `201` criada
- `422` ja existe reserva para a competencia do usuario
- `422` validacao

### PUT/PATCH `/monthly-reserves/{id}`

Atualiza reserva mensal.

Possiveis retornos:

- `200` atualizada
- `404` nao encontrada
- `422` competencia duplicada para o usuario
- `422` validacao

### DELETE `/monthly-reserves/{id}`

Possiveis retornos:

- `204` excluida
- `404` nao encontrada

## Dashboard

### GET `/dashboard/monthly-summary?competency=2026-05`

Retorna consolidado do mes.

Retorno 200:

```json
{
  "data": {
    "competency": "2026-05",
    "total_income": 5000,
    "total_expense": 2200,
    "remaining_amount": 2800,
    "previous_reserve": 1000,
    "current_reserve": 3800,
    "investment": 300,
    "total_saved": 4100,
    "highest_expense_category": "Moradia",
    "lowest_expense_category": "Lazer",
    "expenses_by_category": [
      {
        "category_id": 2,
        "category_name": "Moradia",
        "total": 1200
      }
    ]
  }
}
```

### GET `/dashboard/category-comparison?current_competency=2026-05&previous_competency=2026-04`

Compara gastos por categoria entre dois meses.

Retorno 200:

```json
{
  "data": {
    "current_competency": "2026-05",
    "previous_competency": "2026-04",
    "categories": [
      {
        "category_id": 2,
        "category_name": "Mercado",
        "current_total": 500,
        "previous_total": 420,
        "difference": 80
      }
    ]
  }
}
```

### GET `/dashboard/monthly-evolution?start_competency=2026-01&end_competency=2026-05`

Retorna a evolucao mes a mes.

Possiveis retornos:

- `200` evolucao retornada
- `422` quando a competencia inicial for maior que a final
- `422` validacao

### GET `/dashboard/month-comparison?first_competency=2026-04&second_competency=2026-05`

Retorna comparacao consolidada entre dois meses.

Retorno 200:

```json
{
  "data": {
    "first_month": {
      "competency": "2026-04",
      "total_income": 4500,
      "total_expense": 2000,
      "remaining_amount": 2500,
      "previous_reserve": 800,
      "current_reserve": 3300,
      "investment": 200,
      "total_saved": 3500
    },
    "second_month": {
      "competency": "2026-05",
      "total_income": 5000,
      "total_expense": 2200,
      "remaining_amount": 2800,
      "previous_reserve": 1000,
      "current_reserve": 3800,
      "investment": 300,
      "total_saved": 4100
    },
    "difference": {
      "total_income": 500,
      "total_expense": 200,
      "remaining_amount": 300,
      "current_reserve": 500,
      "total_saved": 600
    }
  }
}
```

## Observacoes importantes

- O token retornado no `register` e no `login` deve ser guardado pelo cliente.
- A API aplica `throttle` nas rotas de autenticacao.
- Os tokens do Sanctum possuem expiracao configurada.
- A API sempre tenta responder em JSON.
- O frontend nunca deve enviar `user_id`. Esse valor vem do usuario autenticado.

## Fluxo recomendado de uso

1. Registrar usuario em `/auth/register`
2. Guardar o token retornado
3. Criar categorias
4. Criar reserva mensal
5. Criar transacoes
6. Consultar dashboard
