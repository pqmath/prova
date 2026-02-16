<?php

namespace Application\Scenarios;

use Application\Factories\OccurrenceFactory;
use Domain\Interfaces\LoggerInterface;
use Domain\Interfaces\ScenarioInterface;
use Domain\ValueObjects\IdempotencyKey;
use Domain\ValueObjects\ScenarioResult;
use Illuminate\Support\Facades\Http;

final class ConcurrencyScenario implements ScenarioInterface
{
    private const CONCURRENT_REQUESTS = 10;

    public function __construct(
        private readonly OccurrenceFactory $occurrenceFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): ScenarioResult
    {
        $this->logger->info('Iniciando cenário: Concorrência', [
            'total_requests' => self::CONCURRENT_REQUESTS,
        ]);

        try {
            $requests = [];
            for ($i = 1; $i <= self::CONCURRENT_REQUESTS; $i++) {
                $occurrence = $this->occurrenceFactory->createRandom();
                $idempotencyKey = IdempotencyKey::generate();

                $requests[] = [
                    'request_number' => $i,
                    'occurrence' => $occurrence,
                    'idempotency_key' => $idempotencyKey,
                ];

                $this->logger->debug("Preparando requisição {$i}/" . self::CONCURRENT_REQUESTS, [
                    'external_id' => $occurrence->getExternalId(),
                    'idempotency_key' => $idempotencyKey->getValue(),
                ]);
            }

            $startTime = microtime(true);

            $httpResponses = Http::pool(function ($pool) use ($requests) {
                return collect($requests)->map(function ($request) use ($pool) {
                    return $pool
                        ->timeout(30)
                        ->withHeaders([
                            'Content-Type' => 'application/json',
                            'Idempotency-Key' => $request['idempotency_key']->getValue(),
                            'X-API-Key' => config('services.core_api.key'),
                        ])
                        ->post(
                            config('services.core_api.url') . '/api/integrations/occurrences',
                            $request['occurrence']->toArray()
                        );
                })->all();
            });

            $endTime = microtime(true);
            $totalTime = round($endTime - $startTime, 2);

            $responses = [];
            $successCount = 0;

            foreach ($httpResponses as $index => $httpResponse) {
                $request = $requests[$index];

                if ($httpResponse instanceof \Throwable) {
                    $statusCode = 0;
                    $isSuccess = false;
                    $body = ['error' => $httpResponse->getMessage()];
                } else {
                    $statusCode = $httpResponse->status();
                    $isSuccess = $statusCode >= 200 && $statusCode < 300;
                    $body = $httpResponse->json();
                }

                if ($isSuccess) {
                    $successCount++;
                }

                $responses[] = [
                    'request_number' => $request['request_number'],
                    'external_id' => $request['occurrence']->getExternalId(),
                    'idempotency_key' => $request['idempotency_key']->getValue(),
                    'status_code' => $statusCode,
                    'success' => $isSuccess,
                    'body' => $body,
                ];

                $this->logger->debug("Resposta da requisição {$request['request_number']}", [
                    'status_code' => $statusCode,
                    'success' => $isSuccess,
                ]);
            }

            $this->logger->info('Cenário de concorrência completo', [
                'total_sent' => self::CONCURRENT_REQUESTS,
                'total_success' => $successCount,
                'total_failed' => self::CONCURRENT_REQUESTS - $successCount,
                'total_time' => $totalTime . 's',
                'avg_time_per_request' => round($totalTime / self::CONCURRENT_REQUESTS, 2) . 's',
            ]);

            if ($successCount === self::CONCURRENT_REQUESTS) {
                return ScenarioResult::success(
                    "Todas as {$successCount} ocorrências foram enviadas COM SUCESSO simultaneamente em {$totalTime}s",
                    202,
                    $responses
                );
            }

            $failedCount = self::CONCURRENT_REQUESTS - $successCount;
            $message = $successCount === 0
                ? "Todas as {$failedCount} requisições falharam"
                : "$successCount de " . self::CONCURRENT_REQUESTS .
                " foram enviadas com sucesso ({$failedCount} falharam)";

            return ScenarioResult::failure(
                $message,
                500,
                $responses
            );

        } catch (\Exception $e) {
            $this->logger->error('Erro no cenário Concorrência', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ScenarioResult::failure(
                'Erro ao executar cenário: ' . $e->getMessage()
            );
        }
    }

    public function getName(): string
    {
        return 'concurrency';
    }

    public function getDescription(): string
    {
        return 'Cenário 3: Concorrência REAL - Envia ' . self::CONCURRENT_REQUESTS . ' ocorrências SIMULTANEAMENTE';
    }
}
