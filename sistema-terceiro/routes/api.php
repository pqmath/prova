<?php

use Illuminate\Support\Facades\Route;
use Application\Services\ScenarioExecutor;

Route::get('/test', function (ScenarioExecutor $executor) {
    return response()->json([
        'message' => 'Classes instaladas com sucesso!',
        'scenarios' => $executor->listScenarios(),
    ]);
});
