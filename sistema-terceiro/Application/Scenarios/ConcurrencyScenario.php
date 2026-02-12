<?php

namespace Application\Scenarios;

use Application\Factories\OccurrenceFactory;
use Domain\Ports\CoreApiClientPort;
use Domain\Ports\LoggerPort;
use Domain\Ports\ScenarioPort;
use Domain\ValueObjects\IdempotencyKey;
use Domain\ValueObjects\ScenarioResult;

final class ConcurrencyScenario implements ScenarioPort
{
    private const CONCURRENT_REQUESTS = 10;

    public function __construct(
        private readonly OccurrenceFactory $occurrenceFactory,
        private readonly CoreApiClientPort $coreApiClient,
        private readonly LoggerPort $logger
    ) {
    }

    public function execute(): ScenarioResult
    {
        $this->logger->info('Iniciando cenário: Concorrência', [
            'total_requests' => self::CONCURRENT_REQUESTS,
        ]);

        try {
            $responses = [];
            $startTime = microtime(true);

            for ($i = 1; $i <= self::CONCURRENT_REQUESTS; $i++) {
                $occurrence = $this->occurrenceFactory->createRandom();
                $idempotencyKey = IdempotencyKey::generate();

                $this->logger->debug("Enviando ocorrência {$i}/" . self::CONCURRENT_REQUESTS, [
                    'external_id' => $occurrence->getExternalId(),
                    'idempotency_key' => $idempotencyKey->getValue(),
                ]);

                $response = $this->coreApiClient->sendOccurrence($occurrence, $idempotencyKey);

                $responses[] = [
                    'request_number' => $i,
                    'external_id' => $occurrence->getExternalId(),
                    'status_code' => $response->getStatusCode(),
                    'success' => $response->isSuccess(),
                ];

                usleep(10000); // 0.01 segundo
            }

            $endTime = microtime(true);
            $totalTime = round($endTime - $startTime, 2);

            $successCount = count(array_filter($responses, fn($r) => $r['success']));

            $this->logger->info('Cenário de concorrência completo', [
                'total_sent' => self::CONCURRENT_REQUESTS,
                'total_success' => $successCount,
                'total_time' => $totalTime . 's',
            ]);

            if ($successCount === self::CONCURRENT_REQUESTS) {
                return ScenarioResult::success(
                    "Todas as {$successCount} ocorrências foram enviadas com sucesso em {$totalTime}s",
                    202,
                    $responses
                );
            }

            return ScenarioResult::failure(
                "Apenas {$successCount} de " . self::CONCURRENT_REQUESTS . " foram enviadas com sucesso",
                500,
                $responses
            );

        } catch (\Exception $e) {
            $this->logger->error('Erro no cenário Concorrência', [
                'error' => $e->getMessage(),
            ]);

            return ScenarioResult::failure(
                'Erro ao executar cenário: ' . $e->getMessage(),
                500
            );
        }
    }

    public function getName(): string
    {
        return 'concurrency';
    }

    public function getDescription(): string
    {
        return 'Cenário 3: Concorrência - Envia ' . self::CONCURRENT_REQUESTS . ' ocorrências simultâneas';
    }
}
