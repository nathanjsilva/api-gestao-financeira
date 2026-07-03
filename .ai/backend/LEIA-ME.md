# LEIA-ME.md — Regras Obrigatórias do Backend

## LEIA ESTE ARQUIVO ANTES DE QUALQUER ALTERAÇÃO NO BACKEND

---

## Arquitetura em Camadas (obrigatória)

```
Request → Controller → Service → Repository → Model (Eloquent)
                ↓
           FormRequest (validação)
                ↓
           Resource (transformação da resposta)
```

**Regras de camada:**
- **Controllers** não contêm lógica de negócio — apenas orquestram
- **Services** contêm toda a lógica de negócio
- **Repositories** contêm toda a lógica de acesso ao banco
- **Models** são apenas estrutura Eloquent (sem lógica de negócio)

---

## Isolamento por Usuário (CRÍTICO)

**Todo acesso a dados DEVE filtrar por `user_id`.**

O `user_id` vem sempre do token autenticado:
```php
$usuarioId = $request->user()->id;
```

Nunca confie em `user_id` vindo do request body ou query params.

---

## Formato de Competência

- Sempre `YYYY-MM` (ex: `2026-07`)
- Validado por `App\Rules\CompetencyRule`
- Use este formato em todos os filtros e armazenamentos

---

## Convenções de Nomenclatura

| Elemento | Convenção | Exemplo |
|----------|-----------|---------|
| Classes | PascalCase | `TransactionService` |
| Métodos | camelCase em português | `listarPorUsuarioId()` |
| Variáveis | camelCase em português | `$usuarioId`, `$competencia` |
| Tabelas | snake_case plural | `monthly_reserves` |
| Colunas | snake_case | `user_id`, `is_recurring` |
| Rotas | kebab-case | `/monthly-reserves` |

---

## Estrutura de Diretórios

```
app/
├── Http/
│   ├── Controllers/Api/     ← apenas controllers da API
│   ├── Middleware/          ← ForceJsonResponse, etc.
│   ├── Requests/            ← Form Requests por módulo
│   └── Resources/           ← API Resources
├── Models/                  ← Eloquent models
├── Repositories/            ← acesso ao banco
├── Rules/                   ← regras de validação customizadas
└── Services/                ← lógica de negócio
```

---

## Regras de Migrations

- **Nunca altere** migrations já aplicadas em produção
- Para adicionar campo: crie nova migration `add_campo_to_tabela`
- Para remover campo: crie nova migration com `dropColumn`
- Sempre inclua o método `down()` para rollback

---

## Respostas da API

Todas as respostas são JSON (Middleware `ForceJsonResponse`).

| Situação | HTTP Status |
|----------|-------------|
| Listagem com dados | 200 |
| Criação com sucesso | 201 |
| Atualização com sucesso | 200 |
| Exclusão com sucesso | 200 ou 204 |
| Não encontrado / sem permissão | 404 |
| Erro de validação | 422 |
| Não autenticado | 401 |

---

## Cache

- `FinancialAnalyticsService` usa cache de **5 minutos**
- Ao alterar qualquer dado que afete o dashboard, verifique se é necessário invalidar o cache
- A chave de cache inclui `user_id` e `competency`

---

## O que verificar antes de alterar o backend

1. Existe FormRequest para validação? Se não, crie um
2. A query filtra por `user_id`? Se não, adicione
3. O retorno usa Resource? Se não, use
4. A rota está protegida por `auth:sanctum`? Se não, adicione
5. Existe teste para o fluxo? Considere criar
