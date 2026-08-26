<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CardCategory\StoreCardCategoryRequest;
use App\Http\Requests\CardCategory\UpdateCardCategoryRequest;
use App\Http\Resources\CardCategoryResource;
use App\Services\CardCategoryService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CardCategoryController extends Controller
{
    public function __construct(
        protected CardCategoryService $cardCategoryService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return CardCategoryResource::collection(
            $this->cardCategoryService->listar($this->usuarioIdAutenticado())
        );
    }

    public function show(int $id): CardCategoryResource|JsonResponse
    {
        $categoria = $this->cardCategoryService->buscarPorId($id, $this->usuarioIdAutenticado());

        if ($categoria === null) {
            return response()->json(['message' => 'Categoria de cartao nao encontrada.'], 404);
        }

        return new CardCategoryResource($categoria);
    }

    public function store(StoreCardCategoryRequest $request): CardCategoryResource|JsonResponse
    {
        try {
            $categoria = $this->cardCategoryService->criar($this->usuarioIdAutenticado(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new CardCategoryResource($categoria))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCardCategoryRequest $request, int $id): CardCategoryResource|JsonResponse
    {
        try {
            $categoria = $this->cardCategoryService->atualizar($id, $this->usuarioIdAutenticado(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return new CardCategoryResource($categoria);
    }

    public function destroy(int $id): Response|JsonResponse
    {
        try {
            $this->cardCategoryService->excluir($id, $this->usuarioIdAutenticado());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->noContent();
    }
}
