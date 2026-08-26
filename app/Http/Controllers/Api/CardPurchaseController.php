<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CardPurchase\ListCardPurchaseRequest;
use App\Http\Requests\CardPurchase\StoreCardPurchaseRequest;
use App\Http\Requests\CardPurchase\UpdateCardPurchaseRequest;
use App\Http\Resources\CardPurchaseResource;
use App\Services\CardPurchaseService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CardPurchaseController extends Controller
{
    public function __construct(
        protected CardPurchaseService $cardPurchaseService,
    ) {}

    public function index(ListCardPurchaseRequest $request): AnonymousResourceCollection
    {
        return CardPurchaseResource::collection(
            $this->cardPurchaseService->listar($this->usuarioIdAutenticado(), $request->validated())
        );
    }

    public function show(int $id): CardPurchaseResource|JsonResponse
    {
        $compra = $this->cardPurchaseService->buscarPorId($id, $this->usuarioIdAutenticado());

        if ($compra === null) {
            return response()->json(['message' => 'Compra de cartao nao encontrada.'], 404);
        }

        return new CardPurchaseResource($compra);
    }

    public function store(StoreCardPurchaseRequest $request): CardPurchaseResource|JsonResponse
    {
        try {
            $compra = $this->cardPurchaseService->criar($this->usuarioIdAutenticado(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new CardPurchaseResource($compra))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCardPurchaseRequest $request, int $id): CardPurchaseResource|JsonResponse
    {
        try {
            $compra = $this->cardPurchaseService->atualizar($id, $this->usuarioIdAutenticado(), $request->validated());
        } catch (DomainException $exception) {
            $status = str_contains($exception->getMessage(), 'nao encontrada') ? 404 : 422;

            return response()->json(['message' => $exception->getMessage()], $status);
        }

        return new CardPurchaseResource($compra);
    }

    public function destroy(int $id): Response|JsonResponse
    {
        try {
            $this->cardPurchaseService->excluir($id, $this->usuarioIdAutenticado());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->noContent();
    }
}
