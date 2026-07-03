# context: monthly-reserve — Reserva Mensal

## Responsabilidade

CRUD de reservas financeiras mensais. Rastreia o valor poupado, investido e observações por competência.

---

## Arquivos Envolvidos

| Tipo | Arquivo |
|------|---------|
| Controller | `app/Http/Controllers/Api/MonthlyReserveController.php` |
| Service | `app/Services/MonthlyReserveService.php` |
| Repository | `app/Repositories/MonthlyReserveRepository.php` |
| Request (criar) | `app/Http/Requests/MonthlyReserve/StoreMonthlyReserveRequest.php` |
| Request (atualizar) | `app/Http/Requests/MonthlyReserve/UpdateMonthlyReserveRequest.php` |
| Resource | `app/Http/Resources/MonthlyReserveResource.php` |
| Model | `app/Models/MonthlyReserve.php` |
| Migration | `database/migrations/2026_05_17_000005_create_monthly_reserves_table.php` |

---

## Estrutura do Model (MonthlyReserve)

```
id                  int (PK)
user_id             int (FK → users)
competency          string(7)      — formato YYYY-MM
reserva_anterior    decimal(10,2)  — valor acumulado de meses anteriores
investimento        decimal(10,2)  — valor investido no mês
observations        text (nullable)
created_at          timestamp
updated_at          timestamp
```

**Constraint única:** `(user_id, competency)` — apenas uma reserva por mês por usuário

---

## Endpoints

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/api/monthly-reserves` | Listar todas as reservas (desc) |
| POST | `/api/monthly-reserves` | Criar reserva |
| GET | `/api/monthly-reserves/{id}` | Buscar por ID |
| PUT | `/api/monthly-reserves/{id}` | Atualizar |
| DELETE | `/api/monthly-reserves/{id}` | Excluir |

---

## Fluxo de Listagem

```
GET /api/monthly-reserves
    → MonthlyReserveController::index()
    → MonthlyReserveService::listar(usuarioId)
    → MonthlyReserveRepository::listarPorUsuarioId(userId)
        → WHERE user_id = $userId
        → ORDER BY competency DESC
    → MonthlyReserveResource::collection()
```

---

## Fluxo de Criação

```
POST /api/monthly-reserves
Body: { competency, reserva_anterior, investimento, observations? }
    → StoreMonthlyReserveRequest (validação)
    → MonthlyReserveController::store()
    → MonthlyReserveService::criar(usuarioId, dados)
        → MonthlyReserveRepository::buscarPorUsuarioIdECompetencia()
            — verifica duplicata para a competência
        → Se já existe → lança exceção (409 Conflict)
        → MonthlyReserveRepository::criar(userId, dados)
    → MonthlyReserveResource (201)
```

---

## Regras de Negócio

1. Apenas uma reserva por `(user_id, competency)` — duplicata é rejeitada
2. `reserva_anterior` representa o patrimônio acumulado até o mês anterior
3. `investimento` representa aportes novos feitos no mês
4. O saldo total da reserva = `reserva_anterior + investimento`
5. `observations` é opcional — campo livre para anotações
6. Listagem ordenada por `competency DESC` — mês mais recente primeiro

---

## Validações (StoreMonthlyReserveRequest)

```php
'competency'       => ['required', new CompetencyRule()],
'reserva_anterior' => ['required', 'numeric', 'min:0'],
'investimento'     => ['required', 'numeric', 'min:0'],
'observations'     => ['nullable', 'string'],
```

---

## Resource de Resposta

```json
{
  "id": 1,
  "competency": "2026-07",
  "reserva_anterior": "15000.00",
  "investimento": "2000.00",
  "observations": "Aporte em fundo DI",
  "created_at": "2026-07-01T10:00:00.000000Z"
}
```

---

## Repository — Métodos Especiais

```php
// Verifica duplicata antes de criar
buscarPorUsuarioIdECompetencia(int $userId, string $competency): ?MonthlyReserve

// Usado pelo DashboardService para evolução da reserva
obterPorUsuarioIdECompetencias(int $userId, array $competencies): Collection
```

---

## Uso no Dashboard

O `FinancialAnalyticsService` usa os dados de reserva para montar `reserve_evolution`:

```json
"reserve_evolution": [
  { "competency": "2026-01", "reserva_anterior": 10000, "investimento": 1500, "total": 11500 },
  { "competency": "2026-02", "reserva_anterior": 11500, "investimento": 2000, "total": 13500 }
]
```

---

## Pontos de Atenção

- `reserva_anterior` deve ser preenchido manualmente pelo usuário (não é calculado automaticamente)
- Não há auto-preenchimento do mês anterior — responsabilidade do usuário manter a consistência
- O campo `observations` pode conter markdown ou texto livre
