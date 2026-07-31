<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReserveAccount\UpsertReserveAccountEntryRequest;
use App\Http\Resources\ReserveAccountEntryResource;
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

    public function update(
        UpsertReserveAccountEntryRequest $request,
        int $contaId,
        string $competencia,
    ): ReserveAccountEntryResource|JsonResponse {
        try {
            $entry = $this->reserveAccountService->definirSaldoDoMes(
                $contaId,
                $this->usuarioIdAutenticado(),
                $competencia,
                $request->validated(),
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return new ReserveAccountEntryResource($entry);
    }

    public function destroy(int $contaId, string $competencia): Response|JsonResponse
    {
        try {
            $this->reserveAccountService->removerSaldoDoMes($contaId, $this->usuarioIdAutenticado(), $competencia);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->noContent();
    }
}
