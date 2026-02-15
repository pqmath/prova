<?php

namespace Application\Http\Controllers;

use Application\UseCases\ListOccurrencesUseCase;
use Application\UseCases\RequestCommandUseCase;
use Domain\Services\LoggerInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OccurrenceController extends Controller
{
    public function __construct(
        private readonly ListOccurrencesUseCase $listUseCase,
        private readonly RequestCommandUseCase $requestCommand,
        private readonly LoggerInterface $logger
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'type', 'search']);
        $this->logger->debug("Listando ocorrências", ['filters' => $filters]);

        $occurrences = $this->listUseCase->execute($filters);
        return response()->json($occurrences);
    }

    public function start(Request $request, string $id): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            $this->logger->warning('Rejeitada requisição de start sem Idempotency-Key');
            return response()->json(['error' => 'Idempotency-Key header is required'], 400);
        }

        $this->logger->info("Solicitando início da ocorrência {$id}");
        try {
            $commandId = $this->requestCommand->execute(
                $idempotencyKey,
                'operator_web',
                'occurrence.started',
                ['id' => $id]
            );
            $this->logger->info("Solicitação de início aceita", ['command_id' => $commandId]);
            return response()->json([
                'message' => 'Start request accepted',
                'commandId' => $commandId,
                'status' => 'accepted'
            ], 202);
        } catch (\Exception $e) {
            $this->logger->error("Erro ao solicitar início da ocorrência {$id}: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function resolve(Request $request, string $id): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            $this->logger->warning('Rejeitada requisição de resolve sem Idempotency-Key');
            return response()->json(['error' => 'Idempotency-Key header is required'], 400);
        }

        $this->logger->info("Solicitando resolução da ocorrência {$id}");
        try {
            $commandId = $this->requestCommand->execute(
                $idempotencyKey,
                'operator_web',
                'occurrence.resolved',
                ['id' => $id]
            );
            $this->logger->info("Solicitação de resolução aceita", ['command_id' => $commandId]);
            return response()->json([
                'message' => 'Resolve request accepted',
                'commandId' => $commandId,
                'status' => 'accepted'
            ], 202);
        } catch (\Exception $e) {
            $this->logger->error("Erro ao solicitar resolução da ocorrência {$id}: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
