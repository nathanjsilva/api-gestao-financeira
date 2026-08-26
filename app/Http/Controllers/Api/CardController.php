<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Card\StoreCardRequest;
use App\Http\Requests\Card\UpdateCardRequest;
use App\Http\Resources\CardResource;
use App\Services\CardService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CardController extends Controller
{
    public function __construct(
        protected CardService $cardService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return CardResource::collection(
            $this->cardService->listar($this->usuarioIdAutenticado())
        );
    }

    public function show(int $id): CardResource|JsonResponse
    {
        $cartao = $this->cardService->buscarPorId($id, $this->usuarioIdAutenticado());

        if ($cartao === null) {
            return response()->json(['message' => 'Cartao nao encontrado.'], 404);
        }

        return new CardResource($cartao);
    }

    public function store(StoreCardRequest $request): CardResource|JsonResponse
    {
        try {
            $cartao = $this->cardService->criar($this->usuarioIdAutenticado(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new CardResource($cartao))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCardRequest $request, int $id): CardResource|JsonResponse
    {
        try {
            $cartao = $this->cardService->atualizar($id, $this->usuarioIdAutenticado(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return new CardResource($cartao);
    }

    public function destroy(int $id): Response|JsonResponse
    {
        try {
            $this->cardService->excluir($id, $this->usuarioIdAutenticado());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->noContent();
    }
}
