<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReserveAccount\DeleteReserveAccountEntryRequest;
use App\Http\Requests\ReserveAccount\UpsertReserveAccountEntriesRequest;
use App\Http\Requests\ReserveAccount\UpsertReserveAccountEntryRequest;
use App\Http\Resources\ReserveAccountEntryResource;
use App\Http\Resources\ReserveAccountStatusEntryResource;
use App\Services\ReserveAccountService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ReserveAccountEntryController extends Controller
{
    public function __construct(
        protected ReserveAccountService $reserveAccountService,
    ) {}

    public function index(int $contaId): AnonymousResourceCollection|JsonResponse
    {
        try {
            $lancamentos = $this->reserveAccountService->listarLancamentos($contaId, $this->usuarioIdAutenticado());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return ReserveAccountEntryResource::collection($lancamentos);
    }

    public function statusHistory(int $contaId): AnonymousResourceCollection|JsonResponse
    {
        try {
            $history = $this->reserveAccountService->obterStatusHistory($contaId, $this->usuarioIdAutenticado());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return ReserveAccountStatusEntryResource::collection($history);
    }

    public function upsertMany(
        UpsertReserveAccountEntriesRequest $request,
    ): AnonymousResourceCollection|JsonResponse {
        try {
            $entries = $this->reserveAccountService->definirSaldosDoMesEmLote(
                $this->usuarioIdAutenticado(),
                $request->validated(),
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return ReserveAccountEntryResource::collection($entries);
    }

    public function update(
        UpsertReserveAccountEntryRequest $request,
        int $contaId,
        string $competencia,
    ): ReserveAccountEntryResource|JsonResponse {
        try {
            $entry = $this->reserveAccountService->definirSaldoDoMes(
                $contaId,
                $this->usuarioIdAutenticado(),
                $request->validated('competency'),
                $request->validated(),
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return new ReserveAccountEntryResource($entry);
    }

    public function destroy(DeleteReserveAccountEntryRequest $request, int $contaId): Response|JsonResponse
    {
        try {
            $this->reserveAccountService->removerSaldoDoMes(
                $contaId,
                $this->usuarioIdAutenticado(),
                $request->validated('competency'),
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->noContent();
    }
}
