# context: categories — Categorias

## Responsabilidade

CRUD de categorias financeiras do usuário. Categorias classificam transações por tipo (entrada ou saída).

---

## Arquivos Envolvidos

| Tipo | Arquivo |
|------|---------|
| Controller | `app/Http/Controllers/Api/CategoryController.php` |
| Service | `app/Services/CategoryService.php` |
| Repository | `app/Repositories/CategoryRepository.php` |
| Request (criar) | `app/Http/Requests/Category/StoreCategoryRequest.php` |
| Request (atualizar) | `app/Http/Requests/Category/UpdateCategoryRequest.php` |
| Resource | `app/Http/Resources/CategoryResource.php` |
| Model | `app/Models/Category.php` |
| Migration | `database/migrations/2026_05_17_000003_create_categories_table.php` |

---

## Estrutura do Model (Category)

```
id          int (PK)
user_id     int (FK → users)
name        string(255)
type        enum('income', 'expense')
created_at  timestamp
updated_at  timestamp
```

**Constraint única:** `(user_id, name, type)` — mesmo usuário não pode ter duas categorias com mesmo nome e tipo

---

## Endpoints

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/api/categories` | Listar todas as categorias do usuário |
| POST | `/api/categories` | Criar categoria |
| GET | `/api/categories/{id}` | Buscar por ID |
| PUT | `/api/categories/{id}` | Atualizar |
| DELETE | `/api/categories/{id}` | Excluir |

---

## Fluxo de Listagem

```
GET /api/categories
    → CategoryController::index()
    → CategoryService::listar(usuarioId)
    → CategoryRepository::listarPorUsuarioId(userId)
        → WHERE user_id = $userId
        → ORDER BY name ASC
    → CategoryResource::collection()
```

---

## Fluxo de Criação

```
POST /api/categories
Body: { name, type }
    → StoreCategoryRequest (valida: name, type)
    → CategoryController::store()
    → CategoryService::criar(usuarioId, dados)
    → CategoryRepository::criar(userId, dados)
    → CategoryResource (201)
```

---

## Regras de Negócio

1. Usuário só acessa suas próprias categorias
2. Não é possível ter duas categorias com mesmo `name` + `type` para o mesmo usuário (unique constraint)
3. `type` só pode ser `income` ou `expense`
4. Ao excluir categoria com transações vinculadas, o banco pode rejeitar (FK constraint) — verificar antes de implementar cascade delete
5. As categorias são usadas como filtro de tipo nas transações — `type` da categoria deve coincidir com `type` da transação

---

## Validações (StoreCategoryRequest)

```php
'name' => ['required', 'string', 'max:255'],
'type' => ['required', 'in:income,expense'],
```

---

## Resource de Resposta

```json
{
  "id": 1,
  "name": "Alimentação",
  "type": "expense"
}
```

---

## Repository — Método Especial

```php
// Usado pelo TransactionService para validar categoria na criação de transação
buscarPorIdTipoEUsuarioId(int $id, string $type, int $userId): ?Category
```

Este método é **crítico** — garante que o usuário não associe uma transação de `expense` a uma categoria de `income` (ou vice-versa).

---

## Pontos de Atenção

- Ao renomear uma categoria, verificar se há transações vinculadas que precisem de atualização no frontend
- Não há listagem paginada — todas as categorias do usuário são retornadas de uma vez
- O frontend usa a listagem de categorias para popular selects nos formulários de transação
