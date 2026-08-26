# project-context.md — Contexto Geral do Projeto

## Visão Geral

**API Gestão Financeira** é uma aplicação full-stack de controle financeiro pessoal com dashboard analítico avançado.

- **Backend**: Laravel 13 (PHP 8.3) — API REST
- **Frontend**: Vue 3 + Pinia + Tailwind CSS 4 — SPA
- **Banco**: MySQL 8 (Docker) / SQLite (local)
- **Autenticação**: Laravel Sanctum (Bearer tokens)
- **Cache**: Redis (Docker)

---

## Stack Completa

| Camada | Tecnologia | Versão |
|--------|-----------|--------|
| Backend framework | Laravel | ^13.0 |
| PHP | PHP | ^8.3 |
| Autenticação | Laravel Sanctum | ^4.3 |
| Frontend framework | Vue.js | ^3.5.13 |
| Roteamento frontend | Vue Router | ^4.6.4 |
| Estado frontend | Pinia | ^3.0.4 |
| HTTP client | Axios | ^1.16.1 |
| CSS | Tailwind CSS | ^4.3.0 |
| Build tool | Vite | ^6.0.5 |
| Banco de dados | MySQL | 8.0 |
| Cache | Redis | alpine |

---

## Modelos de Dados

### User
```
id, name, email, password, email_verified_at, remember_token, created_at, updated_at
```
Relacionamentos: `hasMany(Category)`, `hasMany(Transaction)`, `hasMany(MonthlyReserve)`

### Category
```
id, user_id, name, type (enum: income|expense), created_at, updated_at
```
Constraint única: `(user_id, name, type)`
Relacionamentos: `belongsTo(User)`, `hasMany(Transaction)`

### Transaction
```
id, user_id, category_id, description, amount (decimal:2),
type (income|expense), status (paid|pending),
competency (YYYY-MM), is_recurring (boolean), created_at, updated_at
```
Índices: `(user_id, competency)`, `(category_id, competency)`
Relacionamentos: `belongsTo(User)`, `belongsTo(Category)`

### MonthlyReserve
```
id, user_id, competency (YYYY-MM), reserva_anterior (decimal:2),
investimento (decimal:2), observations (text), created_at, updated_at
```
Constraint única: `(user_id, competency)`
Relacionamentos: `belongsTo(User)`

### Card / CardCategory / CardPurchase / CardInstallment (módulo Cartões)
```
Card:            id, user_id, name, responsible_person, active
CardCategory:    id, user_id, name (unique por usuário), active
CardPurchase:    id, user_id, card_id, card_category_id, description, total_amount,
                 purchase_date, reference_competency (YYYY-MM), payment_type (cash|installment),
                 installments_total, starting_installment_number
CardInstallment: id, card_purchase_id, user_id, card_id, card_category_id, payment_type,
                 installment_number, competency (YYYY-MM), amount
```
Módulo aditivo e isolado — não referencia `categories`/`transactions`, não recalcula dados antigos. Ver `.ai/backend/contexts/cards.md`.

---

## Endpoints da API

**Base URL**: `/api`
**Autenticação**: `Authorization: Bearer {token}` (exceto auth routes)

### Auth (sem autenticação)
| Método | Rota | Descrição |
|--------|------|-----------|
| POST | `/auth/register` | Cadastro (throttle: register) |
| POST | `/auth/login` | Login (throttle: auth) |
| POST | `/auth/logout` | Logout *(requer auth)* |

### Categories (auth:sanctum)
| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/categories` | Listar categorias do usuário |
| POST | `/categories` | Criar categoria |
| GET | `/categories/{id}` | Buscar categoria |
| PUT | `/categories/{id}` | Atualizar categoria |
| DELETE | `/categories/{id}` | Excluir categoria |

### Transactions (auth:sanctum)
| Método | Rota | Params | Descrição |
|--------|------|--------|-----------|
| GET | `/transactions` | `competency` (obrigatório) | Listar por mês |
| POST | `/transactions` | — | Criar transação |
| GET | `/transactions/{id}` | — | Buscar transação |
| PUT | `/transactions/{id}` | — | Atualizar transação |
| DELETE | `/transactions/{id}` | — | Excluir transação |

### Monthly Reserves (auth:sanctum)
| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/monthly-reserves` | Listar reservas (ordenado desc) |
| POST | `/monthly-reserves` | Criar reserva |
| GET | `/monthly-reserves/{id}` | Buscar reserva |
| PUT | `/monthly-reserves/{id}` | Atualizar reserva |
| DELETE | `/monthly-reserves/{id}` | Excluir reserva |

### Dashboard (auth:sanctum)
| Método | Rota | Params | Descrição |
|--------|------|--------|-----------|
| GET | `/dashboard/analytics` | `competency`, `months` (default 6) | Painel analítico completo (cached 5min) |
| GET | `/dashboard/monthly-summary` | `competency` | Resumo mensal com categorias |
| GET | `/dashboard/category-comparison` | `current_competency`, `previous_competency` | Comparativo de categorias |
| GET | `/dashboard/monthly-evolution` | `start_competency`, `end_competency` | Evolução temporal |
| GET | `/dashboard/month-comparison` | `first_competency`, `second_competency` | Comparação entre dois meses |

### Cartões (auth:sanctum) — ver `.ai/backend/contexts/cards.md`
| Método | Rota | Descrição |
|--------|------|-----------|
| GET/POST/PUT/DELETE | `/cards` | CRUD de cartões |
| GET/POST/PUT/DELETE | `/card-categories` | CRUD de categorias de cartão |
| GET/POST/PUT/DELETE | `/card-purchases` | CRUD de compras (`?competency=&card_id=&card_category_id=&payment_type=`) |
| GET | `/card-dashboard/analytics` | `competency`, `months` (default 6) — painel de cartões (cached 5min) |
| GET | `/card-dashboard/monthly-summary` | `competency` | Resumo mensal de cartões |

---

## Infraestrutura Docker

| Serviço | Container | Porta | Credenciais |
|---------|-----------|-------|-------------|
| App (Laravel) | gestao_app | 8000:80 | — |
| MySQL | gestao_db | 3306:3306 | gestao / secret |
| Redis | gestao_redis | — | — |

**Network**: `gestao_net`

---

## Variáveis de Ambiente Relevantes

```env
APP_URL=http://localhost
DB_CONNECTION=mysql
DB_DATABASE=gestao_financeira
DB_USERNAME=gestao
DB_PASSWORD=secret
VITE_API_BASE_URL=http://localhost:8000/api
```
