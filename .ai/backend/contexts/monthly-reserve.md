# context: monthly-reserve — Reserva Mensal

## Responsabilidade

Acompanha a evolução patrimonial do usuário mês a mês, combinando:

1. **Contas de reserva** (`reserve_accounts` + `reserve_account_entries`) — quantidade livre de contas nomeadas pelo usuário (ex: "Nathan", "Esposa", "Reserva de emergência"), cada uma com um saldo declarado por competência. Se o usuário não declarar um novo valor num mês, o saldo é **herdado automaticamente** do último valor declarado em um mês anterior.
2. **Investimentos** (`monthly_reserves` + `monthly_reserve_entries`) — lançamentos itemizados (descrição + valor) por competência, somados automaticamente no campo `investimento` de `monthly_reserves`. Fica sempre separado da reserva.
3. **Saldo do mês** — calculado automaticamente a partir das transações da competência (receitas − despesas), sem nenhuma entrada manual.

---

## Arquivos Envolvidos

### Contas de reserva
| Tipo | Arquivo |
|------|---------|
| Controller | `app/Http/Controllers/Api/ReserveAccountController.php` |
| Controller (saldo por mês) | `app/Http/Controllers/Api/ReserveAccountEntryController.php` |
| Service | `app/Services/ReserveAccountService.php` |
| Repository | `app/Repositories/ReserveAccountRepository.php` |
| Repository (saldos por mês) | `app/Repositories/ReserveAccountEntryRepository.php` |
| Requests | `app/Http/Requests/ReserveAccount/*.php` |
| Resources | `app/Http/Resources/ReserveAccountResource.php`, `ReserveAccountEntryResource.php` |
| Models | `app/Models/ReserveAccount.php`, `app/Models/ReserveAccountEntry.php` |
| Migrations | `database/migrations/2026_07_31_*` |

### Investimentos (inalterado, itemizado)
| Tipo | Arquivo |
|------|---------|
| Controller | `app/Http/Controllers/Api/MonthlyReserveController.php`, `MonthlyReserveEntryController.php` |
| Service | `app/Services/MonthlyReserveService.php` |
| Repository | `app/Repositories/MonthlyReserveRepository.php`, `MonthlyReserveEntryRepository.php` |
| Model | `app/Models/MonthlyReserve.php`, `app/Models/MonthlyReserveEntry.php` |

### Saldo do mês / agregação
| Tipo | Arquivo |
|------|---------|
| Service | `app/Services/DashboardService.php` (`obterResumosBaseIndexados`) |

---

## Estrutura dos Models

### ReserveAccount
```
id          int (PK)
user_id     int (FK → users)
name        string(255)     — rótulo livre, sem unicidade
active      boolean         — arquivada = false (nunca é excluída)
```

### ReserveAccountEntry
```
id                  int (PK)
reserve_account_id  int (FK → reserve_accounts)
competency          string(7)  — formato YYYY-MM
balance             decimal(12,2)  — saldo DECLARADO da conta naquele mês (não é um delta/movimento)
note                text (nullable)
```
Constraint única: `(reserve_account_id, competency)` — no máximo uma declaração por conta e por mês.

### MonthlyReserve (mantido, sem `reserva_anterior`)
```
id            int (PK)
user_id       int (FK → users)
competency    string(7)
investimento  decimal(12,2)  — somado automaticamente a partir de monthly_reserve_entries
observations  text (nullable)
```

---

## Endpoints

### Contas de reserva
| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/api/reserve-accounts` | Lista contas ativas do usuário. Com `?competency=YYYY-MM`, cada conta traz `current_balance`, `previous_balance`, `delta` e `is_inherited` |
| POST | `/api/reserve-accounts` | Cria conta (`name`) |
| PUT | `/api/reserve-accounts/{id}` | Renomeia e/ou arquiva (`name`, `active`) |
| GET | `/api/reserve-accounts/{id}/entries` | Histórico de saldos declarados (inclui contas arquivadas) |
| PUT | `/api/reserve-accounts/{id}/entries/{competency}` | Declara/atualiza o saldo da conta naquele mês (`balance`, `note?`) — upsert |
| DELETE | `/api/reserve-accounts/{id}/entries/{competency}` | Remove a declaração daquele mês (volta a herdar do mês anterior) |

### Investimentos (inalterado)
| Método | Rota | Descrição |
|--------|------|-----------|
| GET/POST/PUT/DELETE | `/api/monthly-reserves/{id}/entries` | Lançamentos de investimento do mês |

### Reserva mensal (mantido, sem `reserva_anterior`)
| Método | Rota | Descrição |
|--------|------|-----------|
| GET/POST/PUT/DELETE | `/api/monthly-reserves` | CRUD de `investimento` + `observations` por competência |

---

## Regra central: herança automática de saldo

O "saldo vigente" de uma conta em uma competência X é o valor da `ReserveAccountEntry` mais recente com `competency <= X`. Implementado em `ReserveAccountEntryRepository::buscarSaldoVigente()` (uma conta) e `obterSaldoVigenteTotalIndexadoPorCompetencia()` (soma em lote, usado pelo `DashboardService`).

Não existe cálculo de "reserva anterior + aporte" — o valor declarado em cada mês já é o saldo corrente daquela conta, exatamente como funcionava na planilha original do usuário.

---

## Fórmulas (calculadas sempre no backend, nunca digitadas)

```
Saldo do mês       = receitas do mês − despesas do mês
Reserva Atual       = soma do saldo vigente de todas as contas de reserva ativas + Saldo do mês
Total Guardado       = Reserva Atual + investimento (monthly_reserves.investimento)
```

Implementado em `DashboardService::obterResumosBaseIndexados()`, reaproveitado por `/dashboard/monthly-summary`, `/dashboard/analytics` (`reserve_evolution`) e pela listagem de `/monthly-reserves`.

---

## Regras de Negócio

1. Uma conta de reserva pode ser criada livremente pelo usuário — não há limite nem nomes fixos.
2. No máximo uma declaração de saldo por conta e por competência.
3. Contas arquivadas (`active = false`) somem das somas de meses seguintes, mas o histórico continua consultável via `/entries`.
4. Investimento continua completamente separado da reserva — nunca é somado a `current_reserve`, só a `total_saved`.
5. Isolamento por usuário em todas as camadas (`user_id` do token autenticado).

---

## Pontos de Atenção

- Se o usuário declarar manualmente na conta de reserva um valor que já embute a sobra do mês corrente, o saldo do mês pode ser contado duas vezes (uma vez como saldo automático, outra dentro do novo valor declarado). Orientação de uso: ajustar os saldos das contas no fechamento do mês, não no meio dele.
- Migração de dados: o antigo campo único `reserva_anterior` de `monthly_reserves` foi migrado para uma conta chamada "Reserva" por usuário (migration `2026_07_31_000003_backfill_reserve_accounts_from_monthly_reserves.php`) antes de a coluna ser removida.
