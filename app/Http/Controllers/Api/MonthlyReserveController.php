<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MonthlyReserve\StoreMonthlyReserveRequest;
use App\Http\Requests\MonthlyReserve\UpdateMonthlyReserveRequest;
use App\Http\Resources\MonthlyReserveResource;
use App\Services\MonthlyReserveService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MonthlyReserveController extends Controller
{
    public function __construct(
        protected MonthlyReserveService $monthlyReserveService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return MonthlyReserveResource::collection(
            $this->monthlyReserveService->listar($this->usuarioIdAutenticado())
        );
    }

    public function show(int $id): MonthlyReserveResource|JsonResponse
    {
        $reservaMensal = $this->monthlyReserveService->buscarPorId($id, $this->usuarioIdAutenticado());

        if ($reservaMensal === null) {
            return response()->json([
                'message' => 'Reserva mensal nao encontrada.',
            ], 404);
        }

        return new MonthlyReserveResource($reservaMensal);
    }

    public function store(StoreMonthlyReserveRequest $request): MonthlyReserveResource|JsonResponse
    {
        try {
            $reservaMensal = $this->monthlyReserveService->criar($this->usuarioIdAutenticado(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return (new MonthlyReserveResource($reservaMensal))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateMonthlyReserveRequest $request, int $id): MonthlyReserveResource|JsonResponse
    {
        try {
            $reservaMensal = $this->monthlyReserveService->atualizar($id, $this->usuarioIdAutenticado(), $request->validated());
        } catch (DomainException $exception) {
            $status = str_contains($exception->getMessage(), 'nao encontrada') ? 404 : 422;

            return response()->json([
                'message' => $exception->getMessage(),
            ], $status);
        }

        return new MonthlyReserveResource($reservaMensal);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->monthlyReserveService->excluir($id, $this->usuarioIdAutenticado());
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 404);
        }

        return response()->noContent();
    }
}
