<?php

namespace Infrastructure\Adapters;

use Domain\Entities\Occurrence;
use Domain\Ports\CoreApiClientPort;
use Domain\ValueObjects\IdempotencyKey;
use Infrastructure\DTOs\ApiResponse;
use Illuminate\Support\Facades\Http;

final class HttpCoreApiClient implements CoreApiClientPort
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct(?string $baseUrl = null, ?string $apiKey = null, int $timeout = 30)
    {
        $this->baseUrl = $baseUrl ?? config('services.core_api.url', env('CORE_API_URL', 'http://api-core:80'));
        $this->apiKey = $apiKey ?? config('services.core_api.key', env('CORE_API_KEY', ''));
        $this->timeout = $timeout;
    }

    public function sendOccurrence(Occurrence $occurrence, IdempotencyKey $key): ApiResponse
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-API-Key' => $this->apiKey,
                    'Idempotency-Key' => $key->getValue(),
                ])
                ->post($this->baseUrl . '/api/integrations/occurrences', $occurrence->toArray());

            return new ApiResponse(
                $response->status(),
                $response->json() ?? [],
                $response->headers()
            );

        } catch (\Exception $e) {
            return new ApiResponse(503, [
                'error' => 'Service Unavailable',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function checkHealth(): bool
    {
        try {
            $response = Http::timeout(5)
                ->get($this->baseUrl . '/api/health');

            return $response->successful();

        } catch (\Exception $e) {
            return false;
        }
    }
}
