<?php

namespace Application\Scenarios;

use Application\Factories\OccurrenceFactory;
use Domain\Interfaces\CoreApiClientInterface;
use Domain\Interfaces\LoggerInterface;
use Domain\Interfaces\ScenarioInterface;
use Domain\ValueObjects\IdempotencyKey;
use Domain\ValueObjects\ScenarioResult;

final class HappyPathScenario implements ScenarioInterface
{
    public function __construct(
        private readonly OccurrenceFactory $occurrenceFactory,
        private readonly CoreApiClientInterface $coreApiClient,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): ScenarioResult
    {
        $this->logger->info('Iniciando cenário: Caminho Feliz');

        try {
            $occurrence = $this->occurrenceFactory->createRandom();
            $idempotencyKey = IdempotencyKey::generate();

            $this->logger->info('Enviando ocorrência', [
                'external_id' => $occurrence->getExternalId(),
                'type' => $occurrence->getType()->value,
                'idempotency_key' => $idempotencyKey->getValue(),
            ]);

            $response = $this->coreApiClient->sendOccurrence($occurrence, $idempotencyKey);

            $this->logger->info('Resposta recebida', [
                'status_code' => $response->getStatusCode(),
                'body' => $response->getBody(),
            ]);

            if ($response->isAccepted()) {
                return ScenarioResult::success(
                    'Ocorrência enviada com sucesso (caminho feliz)',
                    $response->getStatusCode(),
                    [$response->getBody()]
                );
            }

            return ScenarioResult::failure(
                'Resposta inesperada da API Core',
                $response->getStatusCode(),
                [$response->getBody()]
            );

        } catch (\Exception $e) {
            $this->logger->error('Erro no cenário Caminho Feliz', [
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
        return 'happy-path';
    }

    public function getDescription(): string
    {
        return 'Cenário 1: Caminho Feliz - Envia uma ocorrência válida';
    }
}
