<?php

use Illuminate\Support\Facades\Route;
use Application\Http\Controllers\IntegrationController;
use Application\Http\Controllers\OccurrenceController;
use Application\Http\Controllers\DispatchController;
use Application\Http\Controllers\AuditLogController;

Route::prefix('integrations')->group(function () {
    Route::post('/occurrences', [IntegrationController::class, 'store']);
});

Route::get('/occurrences', [OccurrenceController::class, 'index']);
Route::post('/occurrences/{id}/start', [OccurrenceController::class, 'start']);
Route::post('/occurrences/{id}/resolve', [OccurrenceController::class, 'resolve']);
Route::post('/occurrences/{id}/dispatches', [DispatchController::class, 'store']);
Route::get('/audit-logs', [AuditLogController::class, 'index']);
