<?php

namespace Application\Http\Controllers;

use Domain\Interfaces\CoreApiClientInterface;
use Illuminate\Http\JsonResponse;

final class HealthController extends Controller
{
    public function __construct(
        private readonly CoreApiClientInterface $coreApiClient
    ) {
    }

    public function self(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'sistema-terceiro',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function core(): JsonResponse
    {
        $isHealthy = $this->coreApiClient->checkHealth();

        return response()->json([
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'service' => 'api-core',
            'timestamp' => now()->toIso8601String(),
        ], $isHealthy ? 200 : 503);
    }
}
