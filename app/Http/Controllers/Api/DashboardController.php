<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardCategoryComparisonResource;
use App\Http\Resources\DashboardMonthComparisonResource;
use App\Http\Resources\DashboardMonthlyEvolutionResource;
use App\Http\Resources\DashboardResource;
use App\Services\DashboardService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
    ) {}

    public function resumoMensal(Request $request): DashboardResource
    {
        $dados = $request->validate([
            'competency' => ['required', 'string', 'size:7', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        return new DashboardResource(
            $this->dashboardService->obterResumoMensal((int) auth()->id(), $dados['competency'])
        );
    }

    public function comparativoCategorias(Request $request): DashboardCategoryComparisonResource
    {
        $dados = $request->validate([
            'current_competency' => ['required', 'string', 'size:7', 'regex:/^\d{4}-\d{2}$/'],
            'previous_competency' => ['required', 'string', 'size:7', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        return new DashboardCategoryComparisonResource(
            $this->dashboardService->obterComparativoDeCategorias(
                (int) auth()->id(),
                $dados['current_competency'],
                $dados['previous_competency'],
            )
        );
    }

    public function evolucaoMensal(Request $request): DashboardMonthlyEvolutionResource|JsonResponse
    {
        $dados = $request->validate([
            'start_competency' => ['required', 'string', 'size:7', 'regex:/^\d{4}-\d{2}$/'],
            'end_competency' => ['required', 'string', 'size:7', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        try {
            $evolucao = $this->dashboardService->obterEvolucaoMensal(
                (int) auth()->id(),
                $dados['start_competency'],
                $dados['end_competency'],
            );
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return new DashboardMonthlyEvolutionResource($evolucao);
    }

    public function comparacaoEntreMeses(Request $request): DashboardMonthComparisonResource
    {
        $dados = $request->validate([
            'first_competency' => ['required', 'string', 'size:7', 'regex:/^\d{4}-\d{2}$/'],
            'second_competency' => ['required', 'string', 'size:7', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        return new DashboardMonthComparisonResource(
            $this->dashboardService->obterComparacaoEntreMeses(
                (int) auth()->id(),
                $dados['first_competency'],
                $dados['second_competency'],
            )
        );
    }
}
