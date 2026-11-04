# Contexto: categorias financeiras

## Responsabilidade

CRUD de categorias gerais do usuário para classificar transações de entrada ou saída. Categorias de cartão pertencem a outro módulo e não se relacionam com estas categorias.

## Estrutura e restrições

Category contém user_id, name e type. type aceita income ou expense. Há unicidade por usuário, nome e tipo. A listagem filtra o usuário e ordena por type e name.

## Endpoints

Rotas sob auth:sanctum:

- GET /api/categories lista categorias próprias.
- POST /api/categories cria.
- GET /api/categories/{id} consulta categoria própria.
- PUT/PATCH /api/categories/{id} atualiza parcialmente.
- DELETE /api/categories/{id} exclui; a FK pode rejeitar se houver transações vinculadas.

## Validação e fluxo

StoreCategoryRequest e UpdateCategoryRequest removem espaços do name e validam type e unicidade por usuário/tipo. CategoryService coordena operações e recebe o ID autenticado pelo Controller. CategoryRepository filtra listagens e buscas por user_id.

A compatibilidade entre type da categoria e type da transação é verificada por TransactionService quando a transação é criada ou atualizada. Atualizar o tipo da categoria não reclassifica transações existentes nem faz uma verificação em lote dessas transações.

## Repositório usado por transações

~~~php
buscarPorIdTipoEUsuarioId(int $id, int $userId, string $type): ?Category
~~~

Este método exige simultaneamente ID, usuário e tipo. Uma associação ausente ou incompatível gera erro de domínio no fluxo de transação.

## Arquivos principais

- Controller: app/Http/Controllers/Api/CategoryController.php
- Service: app/Services/CategoryService.php
- Repository: app/Repositories/CategoryRepository.php
- Requests: app/Http/Requests/Category/
- Resource: app/Http/Resources/CategoryResource.php
- Model: app/Models/Category.php
- Migration: database/migrations/2026_05_17_000003_create_categories_table.php