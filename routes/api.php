<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MonthlyReserveController;
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

    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics']);
    Route::get('/dashboard/monthly-summary', [DashboardController::class, 'resumoMensal']);
    Route::get('/dashboard/category-comparison', [DashboardController::class, 'comparativoCategorias']);
    Route::get('/dashboard/monthly-evolution', [DashboardController::class, 'evolucaoMensal']);
    Route::get('/dashboard/month-comparison', [DashboardController::class, 'comparacaoEntreMeses']);
});
