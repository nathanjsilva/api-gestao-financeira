<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReserveAccount\ListReserveAccountRequest;
use App\Http\Requests\ReserveAccount\StoreReserveAccountRequest;
use App\Http\Requests\ReserveAccount\UpdateReserveAccountRequest;
use App\Http\Resources\ReserveAccountResource;
use App\Services\ReserveAccountService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReserveAccountController extends Controller
{
    public function __construct(
        protected ReserveAccountService $reserveAccountService,
    ) {}

    public function index(ListReserveAccountRequest $request): AnonymousResourceCollection
    {
        $competencia = $request->validated('competency');
        $usuarioId = $this->usuarioIdAutenticado();

        $contas = $competencia !== null
            ? $this->reserveAccountService->listarComSaldo($usuarioId, $competencia)
            : $this->reserveAccountService->listar($usuarioId);

        return ReserveAccountResource::collection($contas);
    }

    public function store(StoreReserveAccountRequest $request): JsonResponse
    {
        $conta = $this->reserveAccountService->criar($this->usuarioIdAutenticado(), $request->validated());

        return (new ReserveAccountResource($conta))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateReserveAccountRequest $request, int $id): ReserveAccountResource|JsonResponse
    {
        try {
            $conta = $this->reserveAccountService->atualizar($id, $this->usuarioIdAutenticado(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return new ReserveAccountResource($conta);
    }
}
