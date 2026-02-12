<?php

namespace Application\Scenarios;

use Application\Factories\OccurrenceFactory;
use Domain\Interfaces\CoreApiClientInterface;
use Domain\Interfaces\LoggerInterface;
use Domain\Interfaces\ScenarioInterface;
use Domain\ValueObjects\IdempotencyKey;
use Domain\ValueObjects\ScenarioResult;

final class IdempotencyScenario implements ScenarioInterface
{
    public function __construct(
        private readonly OccurrenceFactory $occurrenceFactory,
        private readonly CoreApiClientInterface $coreApiClient,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): ScenarioResult
    {
        $this->logger->info('Iniciando cenário: Idempotência');

        try {
            $occurrence = $this->occurrenceFactory->createRandom();
            $idempotencyKey = IdempotencyKey::generate();

            $responses = [];

            $this->logger->info('Enviando ocorrência (1ª vez)', [
                'external_id' => $occurrence->getExternalId(),
                'idempotency_key' => $idempotencyKey->getValue(),
            ]);

            $response1 = $this->coreApiClient->sendOccurrence($occurrence, $idempotencyKey);
            $responses[] = [
                'attempt' => 1,
                'status_code' => $response1->getStatusCode(),
                'body' => $response1->getBody(),
            ];

            usleep(500000);

            $this->logger->info('Enviando ocorrência (2ª vez - duplicata)', [
                'external_id' => $occurrence->getExternalId(),
                'idempotency_key' => $idempotencyKey->getValue(),
            ]);

            $response2 = $this->coreApiClient->sendOccurrence($occurrence, $idempotencyKey);
            $responses[] = [
                'attempt' => 2,
                'status_code' => $response2->getStatusCode(),
                'body' => $response2->getBody(),
            ];

            $this->logger->info('Teste de idempotência completo', [
                'response_1' => $response1->getStatusCode(),
                'response_2' => $response2->getStatusCode(),
            ]);

            if ($response1->isAccepted() && $response2->isAccepted()) {
                return ScenarioResult::success(
                    'Teste de idempotência executado. Backend deve detectar duplicata internamente.',
                    202,
                    $responses
                );
            }

            return ScenarioResult::failure(
                'Respostas inesperadas no teste de idempotência',
                500,
                $responses
            );

        } catch (\Exception $e) {
            $this->logger->error('Erro no cenário Idempotência', [
                'error' => $e->getMessage(),
            ]);

            return ScenarioResult::failure(
                'Erro ao executar cenário: ' . $e->getMessage()
            );
        }
    }

    public function getName(): string
    {
        return 'idempotency';
    }

    public function getDescription(): string
    {
        return 'Cenário 2: Idempotência - Envia mesma ocorrência 2x com mesma chave';
    }
}
