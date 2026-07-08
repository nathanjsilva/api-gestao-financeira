<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MonthlyReserve\StoreMonthlyReserveEntryRequest;
use App\Http\Requests\MonthlyReserve\UpdateMonthlyReserveEntryRequest;
use App\Http\Resources\MonthlyReserveEntryResource;
use App\Services\MonthlyReserveService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class MonthlyReserveEntryController extends Controller
{
    public function __construct(
        protected MonthlyReserveService $monthlyReserveService,
    ) {}

    public function index(int $reservaId): AnonymousResourceCollection|JsonResponse
    {
        try {
            $lancamentos = $this->monthlyReserveService->listarLancamentos($reservaId, $this->usuarioIdAutenticado());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return MonthlyReserveEntryResource::collection($lancamentos);
    }

    public function store(StoreMonthlyReserveEntryRequest $request, int $reservaId): MonthlyReserveEntryResource|JsonResponse
    {
        try {
            $lancamento = $this->monthlyReserveService->criarLancamento(
                $reservaId,
                $this->usuarioIdAutenticado(),
                $request->validated()
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return (new MonthlyReserveEntryResource($lancamento))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateMonthlyReserveEntryRequest $request, int $reservaId, int $lancamentoId): MonthlyReserveEntryResource|JsonResponse
    {
        try {
            $lancamento = $this->monthlyReserveService->atualizarLancamento(
                $reservaId,
                $lancamentoId,
                $this->usuarioIdAutenticado(),
                $request->validated()
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return new MonthlyReserveEntryResource($lancamento);
    }

    public function destroy(int $reservaId, int $lancamentoId): Response|JsonResponse
    {
        try {
            $this->monthlyReserveService->excluirLancamento($reservaId, $lancamentoId, $this->usuarioIdAutenticado());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->noContent();
    }
}
