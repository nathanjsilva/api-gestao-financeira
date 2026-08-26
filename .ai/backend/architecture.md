# architecture.md — Arquitetura do Backend

## Visão Geral

O backend segue o padrão **Service + Repository** com camadas bem definidas, inspirado em DDD mas adaptado para Laravel.

---

## Fluxo de Dependências

```
routes/api.php
    └── Controller (app/Http/Controllers/Api/)
            ├── FormRequest (app/Http/Requests/) — validação automática
            ├── Service (app/Services/) — lógica de negócio
            │       └── Repository (app/Repositories/) — acesso ao banco
            │               └── Model (app/Models/) — Eloquent ORM
            └── Resource (app/Http/Resources/) — transformação da resposta
```

---

## Controllers

**Responsabilidade única**: receber requisição, delegar ao Service, retornar Resource.

```php
class TransactionController extends Controller
{
    public function __construct(private TransactionService $service) {}

    public function index(ListTransactionRequest $request): JsonResponse
    {
        $usuarioId = $request->user()->id;
        $competencia = $request->validated('competency');
        $transactions = $this->service->listar($usuarioId, $competencia);
        return TransactionResource::collection($transactions)->response();
    }
}
```

**Regras:**
- Não conter queries Eloquent
- Não conter regras de negócio
- Não conter lógica condicional além do roteamento básico
- Sempre usar FormRequest para validação
- Sempre usar Resource para resposta

---

## Services

**Responsabilidade**: toda lógica de negócio, orquestração entre Repositories.

```php
class TransactionService
{
    public function __construct(
        private TransactionRepository $repository,
        private CategoryRepository $categoryRepository
    ) {}

    public function criar(int $usuarioId, array $dados): Transaction
    {
        // Valida que categoria pertence ao usuário e tem o tipo correto
        $categoria = $this->categoryRepository
            ->buscarPorIdTipoEUsuarioId($dados['category_id'], $dados['type'], $usuarioId);

        if (!$categoria) {
            throw new \InvalidArgumentException('Categoria inválida para este tipo de transação.');
        }

        return $this->repository->criar($usuarioId, $dados);
    }
}
```

**Regras:**
- Receber e retornar dados, não requests/responses
- Lançar exceções de domínio quando necessário
- Coordenar múltiplos Repositories quando necessário
- Nunca acessar `$request` diretamente

---

## Repositories

**Responsabilidade**: todo acesso ao banco de dados via Eloquent.

```php
class TransactionRepository
{
    public function listarPorUsuarioIdECompetencia(int $userId, string $competency): Collection
    {
        return Transaction::where('user_id', $userId)
            ->where('competency', $competency)
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
```

**Regras:**
- Sempre filtrar por `user_id` quando aplicável
- Usar eager loading (`with()`) quando necessário para evitar N+1
- Não conter lógica de negócio
- Métodos com nomes descritivos em português

---

## Models

Apenas estrutura Eloquent — relacionamentos, casts, fillable.

```php
class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'description',
        'amount', 'type', 'status', 'competency', 'is_recurring'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_recurring' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

---

## Form Requests

Validação automática antes de chegar ao Controller.

```php
class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'category_id'  => ['required', 'integer'],
            'description'  => ['required', 'string', 'max:255'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'type'         => ['required', 'in:income,expense'],
            'status'       => ['required', 'in:paid,pending'],
            'competency'   => ['required', new CompetencyRule()],
            'is_recurring' => ['boolean'],
        ];
    }
}
```

---

## Resources

Transformação da resposta antes de serializar para JSON.

```php
class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'description'  => $this->description,
            'amount'       => $this->amount,
            'type'         => $this->type,
            'status'       => $this->status,
            'competency'   => $this->competency,
            'is_recurring' => $this->is_recurring,
            'category'     => new CategoryResource($this->whenLoaded('category')),
            'created_at'   => $this->created_at,
        ];
    }
}
```

---

## Middlewares

| Middleware | Aplicação | Função |
|-----------|-----------|--------|
| `ForceJsonResponse` | Global | Força `Accept: application/json` |
| `auth:sanctum` | Rotas protegidas | Valida Bearer token |
| `throttle:register` | POST /auth/register | Rate limiting no cadastro |
| `throttle:auth` | POST /auth/login | Rate limiting no login |

---

## Serviços Especializados de Analytics

```
DashboardService              — orquestra os 5 endpoints de dashboard
FinancialAnalyticsService     — painel analítico completo (cached 5min)
CategoryAnalyticsService      — ranking e crescimento de categorias
MonthlyComparisonService      — comparação entre meses

CardDashboardService          — painel analítico do módulo de cartões (cache próprio, 5min)
CardAnalyticsService          — ranking/crescimento de categorias de cartão, concentração, gasto atípico
```
Os serviços de `Card*` são deliberadamente independentes dos serviços acima — o módulo de cartões (`.ai/backend/contexts/cards.md`) tem cache e evento de invalidação próprios, sem acoplar com o dashboard financeiro geral.

**Dados retornados pelo painel analítico (`/dashboard/analytics`):**
- `filters` — competência e número de meses analisados
- `overview` — totais do mês (entradas, saídas, saldo)
- `comparison` — comparação com mês anterior
- `kpis` — taxa de economia, renda comprometida
- `categories` — ranking de gastos por categoria
- `evolution` — evolução mensal (série temporal)
- `reserve_evolution` — evolução da reserva financeira
- `cash_flow` — fluxo de caixa (entradas vs saídas vs restante)
- `heatmap` — mapa de calor de gastos
- `insights` — insights automáticos gerados pelo sistema
