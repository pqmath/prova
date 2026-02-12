<?php

namespace Application\Scenarios;

use Application\Factories\OccurrenceFactory;
use Domain\Ports\CoreApiClientPort;
use Domain\Ports\LoggerPort;
use Domain\Ports\ScenarioPort;
use Domain\ValueObjects\IdempotencyKey;
use Domain\ValueObjects\ScenarioResult;

final class UpdateScenario implements ScenarioPort
{
    public function __construct(
        private readonly OccurrenceFactory $occurrenceFactory,
        private readonly CoreApiClientPort $coreApiClient,
        private readonly LoggerPort $logger
    ) {
    }

    public function execute(): ScenarioResult
    {
        $this->logger->info('Iniciando cenário: Atualização de Ocorrência');

        try {
            $occurrence = $this->occurrenceFactory->createRandom();
            $idempotencyKey1 = IdempotencyKey::generate();

            $responses = [];

            // PRIMEIRO ENVIO (criação)
            $this->logger->info('Enviando ocorrência inicial', [
                'external_id' => $occurrence->getExternalId(),
                'description' => $occurrence->getDescription(),
                'idempotency_key' => $idempotencyKey1->getValue(),
            ]);

            $response1 = $this->coreApiClient->sendOccurrence($occurrence, $idempotencyKey1);
            $responses[] = [
                'type' => 'creation',
                'status_code' => $response1->getStatusCode(),
                'body' => $response1->getBody(),
            ];

            sleep(1);

            // SEGUNDO ENVIO (atualização)
            $updatedOccurrence = $occurrence->withUpdatedDescription(
                $occurrence->getDescription() . ' [ATUALIZADO - Informações complementares]'
            );
            $idempotencyKey2 = IdempotencyKey::generate();

            $this->logger->info('Enviando atualização da ocorrência', [
                'external_id' => $updatedOccurrence->getExternalId(),
                'description' => $updatedOccurrence->getDescription(),
                'idempotency_key' => $idempotencyKey2->getValue(),
            ]);

            $response2 = $this->coreApiClient->sendOccurrence($updatedOccurrence, $idempotencyKey2);
            $responses[] = [
                'type' => 'update',
                'status_code' => $response2->getStatusCode(),
                'body' => $response2->getBody(),
            ];

            $this->logger->info('Teste de atualização completo', [
                'response_creation' => $response1->getStatusCode(),
                'response_update' => $response2->getStatusCode(),
            ]);

            if ($response1->isAccepted() && $response2->isAccepted()) {
                return ScenarioResult::success(
                    'Ocorrência criada e atualizada com sucesso',
                    202,
                    $responses
                );
            }

            return ScenarioResult::failure(
                'Respostas inesperadas no teste de atualização',
                500,
                $responses
            );

        } catch (\Exception $e) {
            $this->logger->error('Erro no cenário Atualização', [
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
        return 'update';
    }

    public function getDescription(): string
    {
        return 'Cenário 4: Atualização - Envia ocorrência e depois atualiza';
    }
}
