<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CardDashboard\CardAnalyticsRequest;
use App\Http\Requests\CardDashboard\CardMonthlySummaryRequest;
use App\Http\Resources\CardDashboardAnalyticsResource;
use App\Http\Resources\CardMonthlySummaryResource;
use App\Services\CardDashboardService;

class CardDashboardController extends Controller
{
    public function __construct(
        protected CardDashboardService $cardDashboardService,
    ) {}

    public function analytics(CardAnalyticsRequest $request): CardDashboardAnalyticsResource
    {
        return new CardDashboardAnalyticsResource(
            $this->cardDashboardService->obterPainelAnalitico(
                $this->usuarioIdAutenticado(),
                $request->validated('competency'),
                (int) ($request->validated('months') ?? 6),
            )
        );
    }

    public function resumoMensal(CardMonthlySummaryRequest $request): CardMonthlySummaryResource
    {
        return new CardMonthlySummaryResource(
            $this->cardDashboardService->obterResumoMensal(
                $this->usuarioIdAutenticado(),
                $request->validated('competency'),
            )
        );
    }
}
