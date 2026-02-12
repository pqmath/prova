<?php

use Application\Http\Controllers\HealthController;
use Application\Http\Controllers\ScenarioController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'self']);
Route::get('/health/core', [HealthController::class, 'core']);

Route::prefix('scenarios')->group(function () {
    Route::get('/', [ScenarioController::class, 'index']);
    Route::post('/execute-all', [ScenarioController::class, 'executeAll']);
    Route::post('/happy-path', [ScenarioController::class, 'happyPath']);
    Route::post('/idempotency', [ScenarioController::class, 'idempotency']);
    Route::post('/concurrency', [ScenarioController::class, 'concurrency']);
    Route::post('/update', [ScenarioController::class, 'update']);
});
