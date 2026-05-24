<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CategoryComparisonRequest;
use App\Http\Requests\Dashboard\FinancialAnalyticsRequest;
use App\Http\Requests\Dashboard\MonthComparisonRequest;
use App\Http\Requests\Dashboard\MonthlyEvolutionRequest;
use App\Http\Requests\Dashboard\MonthlySummaryRequest;
use App\Http\Resources\DashboardAnalyticsResource;
use App\Http\Resources\DashboardCategoryComparisonResource;
use App\Http\Resources\DashboardMonthComparisonResource;
use App\Http\Resources\DashboardMonthlyEvolutionResource;
use App\Http\Resources\DashboardResource;
use App\Services\DashboardService;
use App\Services\FinancialAnalyticsService;
use DomainException;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected FinancialAnalyticsService $financialAnalyticsService,
    ) {}

    public function analytics(FinancialAnalyticsRequest $request): DashboardAnalyticsResource
    {
        return new DashboardAnalyticsResource(
            $this->financialAnalyticsService->obterPainelAnalitico(
                $this->usuarioIdAutenticado(),
                $request->validated('competency'),
                (int) ($request->validated('months') ?? 6),
            )
        );
    }

    public function resumoMensal(MonthlySummaryRequest $request): DashboardResource
    {
        return new DashboardResource(
            $this->dashboardService->obterResumoMensal($this->usuarioIdAutenticado(), $request->validated('competency'))
        );
    }

    public function comparativoCategorias(CategoryComparisonRequest $request): DashboardCategoryComparisonResource
    {
        return new DashboardCategoryComparisonResource(
            $this->dashboardService->obterComparativoDeCategorias(
                $this->usuarioIdAutenticado(),
                $request->validated('current_competency'),
                $request->validated('previous_competency'),
            )
        );
    }

    public function evolucaoMensal(MonthlyEvolutionRequest $request): DashboardMonthlyEvolutionResource|JsonResponse
    {
        try {
            $evolucao = $this->dashboardService->obterEvolucaoMensal(
                $this->usuarioIdAutenticado(),
                $request->validated('start_competency'),
                $request->validated('end_competency'),
            );
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return new DashboardMonthlyEvolutionResource($evolucao);
    }

    public function comparacaoEntreMeses(MonthComparisonRequest $request): DashboardMonthComparisonResource
    {
        return new DashboardMonthComparisonResource(
            $this->dashboardService->obterComparacaoEntreMeses(
                $this->usuarioIdAutenticado(),
                $request->validated('first_competency'),
                $request->validated('second_competency'),
            )
        );
    }
}
