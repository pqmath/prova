<?php

namespace Application\Http\Controllers;

use Application\Services\ScenarioExecutor;
use Illuminate\Http\JsonResponse;

final class ScenarioController extends Controller
{
    public function __construct(
        private readonly ScenarioExecutor $executor
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'scenarios' => $this->executor->listScenarios(),
            'total' => $this->executor->count(),
        ]);
    }

    public function executeAll(): JsonResponse
    {
        $results = $this->executor->executeAll();

        return response()->json([
            'message' => 'Todos os cenários executados',
            'results' => $results,
        ]);
    }

    public function happyPath(): JsonResponse
    {
        $result = $this->executor->executeByName('happy-path');

        return response()->json([
            'scenario' => 'happy-path',
            'result' => $result->toArray(),
        ], $result->isSuccess() ? 200 : 500);
    }

    public function idempotency(): JsonResponse
    {
        $result = $this->executor->executeByName('idempotency');

        return response()->json([
            'scenario' => 'idempotency',
            'result' => $result->toArray(),
        ], $result->isSuccess() ? 200 : 500);
    }

    public function concurrency(): JsonResponse
    {
        $result = $this->executor->executeByName('concurrency');

        return response()->json([
            'scenario' => 'concurrency',
            'result' => $result->toArray(),
        ], $result->isSuccess() ? 200 : 500);
    }

    public function update(): JsonResponse
    {
        $result = $this->executor->executeByName('update');

        return response()->json([
            'scenario' => 'update',
            'result' => $result->toArray(),
        ], $result->isSuccess() ? 200 : 500);
    }
}
