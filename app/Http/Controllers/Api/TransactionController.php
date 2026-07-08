<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\ListTransactionRequest;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
    ) {}

    public function index(ListTransactionRequest $request): AnonymousResourceCollection
    {
        return TransactionResource::collection(
            $this->transactionService->listar($this->usuarioIdAutenticado(), $request->validated('competency'))
        );
    }

    public function show(int $id): TransactionResource|JsonResponse
    {
        $transacao = $this->transactionService->buscarPorId($id, $this->usuarioIdAutenticado());

        if ($transacao === null) {
            return response()->json([
                'message' => 'Transacao nao encontrada.',
            ], 404);
        }

        return new TransactionResource($transacao);
    }

    public function store(StoreTransactionRequest $request): TransactionResource|JsonResponse
    {
        try {
            $transacao = $this->transactionService->criar($this->usuarioIdAutenticado(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return (new TransactionResource($transacao))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateTransactionRequest $request, int $id): TransactionResource|JsonResponse
    {
        try {
            $transacao = $this->transactionService->atualizar($id, $this->usuarioIdAutenticado(), $request->validated());
        } catch (DomainException $exception) {
            $status = $exception->getMessage() === 'Transacao nao encontrada para este usuario.' ? 404 : 422;

            return response()->json([
                'message' => $exception->getMessage(),
            ], $status);
        }

        return new TransactionResource($transacao);
    }

    public function destroy(int $id): Response|JsonResponse
    {
        try {
            $this->transactionService->excluir($id, $this->usuarioIdAutenticado());
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 404);
        }

        return response()->noContent();
    }
}
