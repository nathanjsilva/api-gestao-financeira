<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MonthlyReserveController;
use App\Http\Controllers\Api\MonthlyReserveEntryController;
use App\Http\Controllers\Api\ReserveAccountController;
use App\Http\Controllers\Api\ReserveAccountEntryController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('transactions', TransactionController::class);
    Route::apiResource('monthly-reserves', MonthlyReserveController::class);
    Route::apiResource('monthly-reserves.entries', MonthlyReserveEntryController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::apiResource('reserve-accounts', ReserveAccountController::class)
        ->only(['index', 'store', 'update']);
    Route::get('/reserve-accounts/{contaId}/entries', [ReserveAccountEntryController::class, 'index']);
    Route::put('/reserve-accounts/{contaId}/entries/{competencia}', [ReserveAccountEntryController::class, 'update'])
        ->where('competencia', '\d{4}-\d{2}');
    Route::delete('/reserve-accounts/{contaId}/entries/{competencia}', [ReserveAccountEntryController::class, 'destroy'])
        ->where('competencia', '\d{4}-\d{2}');

    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics']);
    Route::get('/dashboard/monthly-summary', [DashboardController::class, 'resumoMensal']);
    Route::get('/dashboard/category-comparison', [DashboardController::class, 'comparativoCategorias']);
    Route::get('/dashboard/monthly-evolution', [DashboardController::class, 'evolucaoMensal']);
    Route::get('/dashboard/month-comparison', [DashboardController::class, 'comparacaoEntreMeses']);
});
