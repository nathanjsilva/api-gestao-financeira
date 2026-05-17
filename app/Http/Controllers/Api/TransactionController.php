<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $dados = $request->validate([
            'competency' => ['required', 'string', 'size:7', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        return TransactionResource::collection(
            $this->transactionService->listar((int) auth()->id(), $dados['competency'])
        );
    }

    public function show(int $id): TransactionResource|JsonResponse
    {
        $transacao = $this->transactionService->buscarPorId($id, (int) auth()->id());

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
            $transacao = $this->transactionService->criar((int) auth()->id(), $request->validated());
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
            $transacao = $this->transactionService->atualizar($id, (int) auth()->id(), $request->validated());
        } catch (DomainException $exception) {
            $status = $exception->getMessage() === 'Transacao nao encontrada para este usuario.' ? 404 : 422;

            return response()->json([
                'message' => $exception->getMessage(),
            ], $status);
        }

        return new TransactionResource($transacao);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->transactionService->excluir($id, (int) auth()->id());
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 404);
        }

        return response()->noContent();
    }
}
