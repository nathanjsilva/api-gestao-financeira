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

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            $this->categoryService->listar((int) auth()->id())
        );
    }

    public function show(int $id): CategoryResource|JsonResponse
    {
        $categoria = $this->categoryService->buscarPorId($id, (int) auth()->id());

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
            $categoria = $this->categoryService->criar((int) auth()->id(), $request->validated());
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
            $categoria = $this->categoryService->atualizar((int) $id, (int) auth()->id(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 404);
        }

        return new CategoryResource($categoria);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->categoryService->excluir($id, (int) auth()->id());
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 404);
        }

        return response()->noContent();
    }
}
