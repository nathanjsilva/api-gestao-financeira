<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            $this->categoryService->listar($this->usuarioIdAutenticado())
        );
    }

    public function show(int $id): CategoryResource|JsonResponse
    {
        $categoria = $this->categoryService->buscarPorId($id, $this->usuarioIdAutenticado());

        if ($categoria === null) {
            return response()->json([
                'message' => 'Categoria nao encontrada.',
            ], 404);
        }

        return new CategoryResource($categoria);
    }

    public function store(StoreCategoryRequest $request): CategoryResource|JsonResponse
    {
        try {
            $categoria = $this->categoryService->criar($this->usuarioIdAutenticado(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return (new CategoryResource($categoria))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCategoryRequest $request, int $id): CategoryResource|JsonResponse
    {
        try {
            $categoria = $this->categoryService->atualizar((int) $id, $this->usuarioIdAutenticado(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 404);
        }

        return new CategoryResource($categoria);
    }

    public function destroy(int $id): Response|JsonResponse
    {
        try {
            $this->categoryService->excluir($id, $this->usuarioIdAutenticado());
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 404);
        }

        return response()->noContent();
    }
}
