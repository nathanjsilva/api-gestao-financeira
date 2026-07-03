# context: transactions — Transações Financeiras

## Responsabilidade

CRUD de transações financeiras do usuário, filtradas por competência (mês/ano).

---

## Arquivos Envolvidos

| Tipo | Arquivo |
|------|---------|
| Controller | `app/Http/Controllers/Api/TransactionController.php` |
| Service | `app/Services/TransactionService.php` |
| Repository | `app/Repositories/TransactionRepository.php` |
| Request (listagem) | `app/Http/Requests/Transaction/ListTransactionRequest.php` |
| Request (criar) | `app/Http/Requests/Transaction/StoreTransactionRequest.php` |
| Request (atualizar) | `app/Http/Requests/Transaction/UpdateTransactionRequest.php` |
| Resource | `app/Http/Resources/TransactionResource.php` |
| Rule | `app/Rules/CompetencyRule.php` |
| Model | `app/Models/Transaction.php` |
| Migration | `database/migrations/2026_05_17_000004_create_transactions_table.php` |

---

## Estrutura do Model (Transaction)

```
id              int (PK)
user_id         int (FK → users)
category_id     int (FK → categories)
description     string(255)
amount          decimal(10,2)
type            enum('income', 'expense')
status          enum('paid', 'pending')
competency      string(7)  — formato YYYY-MM
is_recurring    boolean    — default false
created_at      timestamp
updated_at      timestamp
```

**Índices:** `(user_id, competency)`, `(category_id, competency)`

---

## Endpoints

| Método | Rota | Param | Descrição |
|--------|------|-------|-----------|
| GET | `/api/transactions` | `?competency=YYYY-MM` | Listar por mês |
| POST | `/api/transactions` | body JSON | Criar |
| GET | `/api/transactions/{id}` | — | Buscar por ID |
| PUT | `/api/transactions/{id}` | body JSON | Atualizar |
| DELETE | `/api/transactions/{id}` | — | Excluir |

---

## Fluxo de Listagem

```
GET /api/transactions?competency=2026-07
    → ListTransactionRequest (valida: competency obrigatório)
    → TransactionController::index()
    → TransactionService::listar(usuarioId, competencia)
    → TransactionRepository::listarPorUsuarioIdECompetencia()
        → WHERE user_id = $userId AND competency = $competency
        → WITH category (eager load)
        → ORDER BY created_at DESC
    → TransactionResource::collection()
```

---

## Fluxo de Criação

```
POST /api/transactions
Body: { category_id, description, amount, type, status, competency, is_recurring? }
    → StoreTransactionRequest (validação completa)
    → TransactionController::store()
    → TransactionService::criar(usuarioId, dados)
        → CategoryRepository::buscarPorIdTipoEUsuarioId(category_id, type, userId)
            — valida que categoria existe, pertence ao usuário E tem o mesmo tipo
        → TransactionRepository::criar(userId, dados)
    → TransactionResource (201)
```

**Regra crítica**: A `category_id` deve pertencer ao `user_id` autenticado e ter o mesmo `type` da transação. Se não, erro de validação.

---

## Fluxo de Atualização

```
PUT /api/transactions/{id}
    → UpdateTransactionRequest
    → TransactionController::update()
    → TransactionService::atualizar(id, usuarioId, dados)
        → Verifica que transação pertence ao usuário (404 se não)
        → Valida categoria (mesma regra do criar)
        → TransactionRepository::atualizar()
    → TransactionResource (200)
```

---

## Regras de Negócio

1. Usuário só acessa suas próprias transações
2. `category_id` deve pertencer ao usuário E ter o mesmo `type` da transação
3. `competency` deve ser um mês válido no formato `YYYY-MM`
4. `amount` mínimo de `0.01`
5. Transações com `is_recurring = true` são marcadores — não criam automaticamente meses futuros
6. Listagem sempre exige `competency` — não há listagem de todas as transações sem filtro

---

## Validações (StoreTransactionRequest)

```php
'category_id'  => ['required', 'integer', 'min:1'],
'description'  => ['required', 'string', 'max:255'],
'amount'       => ['required', 'numeric', 'min:0.01'],
'type'         => ['required', 'in:income,expense'],
'status'       => ['required', 'in:paid,pending'],
'competency'   => ['required', new CompetencyRule()],
'is_recurring' => ['boolean'],
```

---

## Resource de Resposta

```json
{
  "id": 1,
  "description": "Salário",
  "amount": "5000.00",
  "type": "income",
  "status": "paid",
  "competency": "2026-07",
  "is_recurring": true,
  "category": {
    "id": 2,
    "name": "Salário",
    "type": "income"
  },
  "created_at": "2026-07-01T10:00:00.000000Z"
}
```

---

## Repository — Métodos de Agregação (usados pelo Dashboard)

```php
// Totais por tipo no mês
obterResumoMensalPorTipo(int $userId, string $competency): Collection
// Ex: [{ type: 'income', total: 5000.00 }, { type: 'expense', total: 3200.00 }]

// Totais entre competências (evolução temporal)
obterResumoMensalPorTipoEntreCompetencias(int $userId, string $inicio, string $fim): Collection

// Gastos agrupados por categoria (para ranking)
obterTotaisDeGastosAgrupadosPorCategoria(int $userId, string $competency): Collection

// Gastos por categoria entre períodos
obterTotaisDeGastosPorCategoriaEntreCompetencias(int $userId, string $inicio, string $fim): Collection
```

---

## Pontos de Atenção

- Sempre carregar `category` com `with('category')` na listagem — o Resource usa
- Ao criar transação, a validação de categoria é responsabilidade do **Service**, não do FormRequest
- Não há paginação — todas as transações do mês são retornadas
