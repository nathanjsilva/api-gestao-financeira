# coding-standards.md — Padrões de Código PHP/Laravel

## Geral

- **PHP 8.3** — use recursos modernos: match, named arguments, readonly, fibers quando aplicável
- **Laravel 13** — siga as convenções do framework
- **PSR-12** — padrão de formatação (Laravel Pint para auto-formatação)
- **Tipagem estrita** — sempre declare tipos em parâmetros e retornos

---

## Nomenclatura

### Classes e Arquivos
```php
// Controllers
class TransactionController extends Controller        // PascalCase + sufixo
class AuthController extends Controller

// Services
class TransactionService                              // PascalCase + sufixo
class FinancialAnalyticsService

// Repositories
class TransactionRepository                           // PascalCase + sufixo

// Form Requests
class StoreTransactionRequest extends FormRequest     // Verbo + Entidade + Request
class UpdateTransactionRequest extends FormRequest
class ListTransactionRequest extends FormRequest

// Resources
class TransactionResource extends JsonResource        // Entidade + Resource
class DashboardAnalyticsResource extends JsonResource
```

### Métodos (camelCase em português)
```php
// Services e Repositories — verbos em português
public function listar(int $usuarioId): Collection
public function buscarPorId(int $id, int $usuarioId): ?Transaction
public function buscarPorCompetencia(int $usuarioId, string $competencia): ?MonthlyReserve
public function criar(int $usuarioId, array $dados): Transaction
public function atualizar(int $id, int $usuarioId, array $dados): Transaction
public function excluir(int $id, int $usuarioId): bool
public function obterResumoMensal(int $usuarioId, string $competencia): array
public function montarRanking(array $atual, array $anterior): array
```

### Variáveis
```php
$usuarioId      // não $userId
$competencia    // não $competency
$dados          // array de dados do request
$categoria      // não $category
$transacao      // não $transaction
$reserva        // não $reserve
```

---

## Controllers

```php
class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $service
    ) {}

    public function index(ListTransactionRequest $request): JsonResponse
    {
        $usuarioId  = $request->user()->id;
        $competencia = $request->validated('competency');

        $transacoes = $this->service->listar($usuarioId, $competencia);

        return TransactionResource::collection($transacoes)->response();
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $usuarioId = $request->user()->id;
        $dados     = $request->validated();

        $transacao = $this->service->criar($usuarioId, $dados);

        return (new TransactionResource($transacao))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $usuarioId = $request->user()->id;
        $this->service->excluir($id, $usuarioId);
        return response()->json(['message' => 'Excluído com sucesso.']);
    }
}
```

---

## Services

```php
class TransactionService
{
    public function __construct(
        private readonly TransactionRepository $repository,
        private readonly CategoryRepository    $categoryRepository
    ) {}

    public function criar(int $usuarioId, array $dados): Transaction
    {
        $categoria = $this->categoryRepository
            ->buscarPorIdTipoEUsuarioId($dados['category_id'], $dados['type'], $usuarioId);

        if (!$categoria) {
            throw new \InvalidArgumentException('Categoria inválida.');
        }

        return $this->repository->criar($usuarioId, $dados);
    }

    public function buscarPorId(int $id, int $usuarioId): ?Transaction
    {
        return $this->repository->buscarPorIdEUsuarioId($id, $usuarioId);
    }
}
```

---

## Repositories

```php
class TransactionRepository
{
    public function listarPorUsuarioIdECompetencia(
        int    $userId,
        string $competency
    ): Collection {
        return Transaction::where('user_id', $userId)
            ->where('competency', $competency)
            ->with('category')
            ->orderByDesc('created_at')
            ->get();
    }

    public function criar(int $userId, array $dados): Transaction
    {
        return Transaction::create([
            'user_id'      => $userId,
            'category_id'  => $dados['category_id'],
            'description'  => $dados['description'],
            'amount'       => $dados['amount'],
            'type'         => $dados['type'],
            'status'       => $dados['status'],
            'competency'   => $dados['competency'],
            'is_recurring' => $dados['is_recurring'] ?? false,
        ]);
    }
}
```

---

## Form Requests

```php
class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autorização via auth:sanctum no route
    }

    public function rules(): array
    {
        return [
            'category_id'  => ['required', 'integer', 'min:1'],
            'description'  => ['required', 'string', 'max:255'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'type'         => ['required', 'in:income,expense'],
            'status'       => ['required', 'in:paid,pending'],
            'competency'   => ['required', new CompetencyRule()],
            'is_recurring' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'A categoria é obrigatória.',
            'amount.min'           => 'O valor deve ser maior que zero.',
            'competency.required'  => 'A competência é obrigatória.',
        ];
    }
}
```

---

## Injeção de Dependência

Sempre use constructor injection com `readonly`:
```php
public function __construct(
    private readonly TransactionService    $transactionService,
    private readonly CategoryService       $categoryService
) {}
```

---

## O que NÃO fazer

```php
// ❌ Lógica de negócio no Controller
public function store(Request $request)
{
    if (Transaction::where('user_id', $request->user()->id)->count() > 100) {
        return response()->json(['error' => 'Limite atingido'], 422);
    }
    // ...
}

// ✅ Lógica no Service
public function criar(int $usuarioId, array $dados): Transaction
{
    if ($this->repository->contarPorUsuario($usuarioId) > 100) {
        throw new \DomainException('Limite de transações atingido.');
    }
    // ...
}

// ❌ Query direta no Controller
public function index(Request $request)
{
    $items = Transaction::where('user_id', auth()->id())->get();
}

// ✅ Via Repository
public function index(ListTransactionRequest $request)
{
    $items = $this->service->listar($request->user()->id, $request->validated('competency'));
}
```
