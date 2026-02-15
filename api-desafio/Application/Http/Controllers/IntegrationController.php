<?php

namespace Application\Http\Controllers;

use Application\UseCases\RequestOccurrenceCreationUseCase;
use Domain\Services\LoggerInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IntegrationController extends Controller
{
    public function __construct(
        private readonly RequestOccurrenceCreationUseCase $useCase,
        private readonly LoggerInterface $logger
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            $this->logger->warning('Rejeitada requisição de integração sem Idempotency-Key');
            return response()->json(['error' => 'Idempotency-Key header is required'], 400);
        }

        $data = $request->all();
        $this->logger->info("Recebida requisição de integração", ['idempotency_key' => $idempotencyKey, 'data' => $data]);

        $payload = [
            'externalId' => $data['externalId'] ?? $data['external_id'] ?? null,
            'type' => $data['type'] ?? null,
            'description' => $data['description'] ?? null,
            'reportedAt' => $data['reportedAt'] ?? $data['reported_at'] ?? null,
        ];

        if (!$payload['externalId'] || !$payload['type'] || !$payload['description'] || !$payload['reportedAt']) {
            $this->logger->warning("Requisição de integração inválida (campos faltando)", ['payload' => $payload]);
            return response()->json(['error' => 'Missing required fields (externalId, type, description, reportedAt)'], 422);
        }

        try {
            $commandId = $this->useCase->execute(
                $idempotencyKey,
                'system_integration',
                'occurrence.received',
                $payload
            );

            $this->logger->info("Requisição de integração aceita", ['command_id' => $commandId]);

            return response()->json([
                'commandId' => $commandId,
                'status' => 'accepted'
            ], 202);
        } catch (\Exception $e) {
            $this->logger->error("Erro ao processar requisição de integração: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
