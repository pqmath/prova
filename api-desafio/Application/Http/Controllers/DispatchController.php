<?php

namespace Application\Http\Controllers;


use Application\UseCases\RequestCommandUseCase;
use Domain\Services\LoggerInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DispatchController extends Controller
{
    public function __construct(
        private readonly RequestCommandUseCase $requestCommand,
        private readonly LoggerInterface $logger
    ) {
    }

    public function store(Request $request, string $occurrenceId): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            $this->logger->warning('Rejeitada requisição de despacho sem Idempotency-Key');
            return response()->json(['error' => 'Idempotency-Key header is required'], 400);
        }

        $request->validate([
            'resourceCode' => 'required|string'
        ]);

        $resourceCode = $request->input('resourceCode');
        $this->logger->info("Solicitação de despacho para ocorrência {$occurrenceId}.
        Recurso: {$resourceCode}", ['idempotency_key' => $idempotencyKey]);

        try {
            $commandId = $this->requestCommand->execute(
                $idempotencyKey,
                'operator_web',
                'dispatch.requested',
                [
                    'occurrence_id' => $occurrenceId,
                    'resource_code' => $resourceCode
                ]
            );

            $this->logger->info("Requisição de despacho aceita", ['command_id' => $commandId]);

            return response()->json([
                'message' => 'Dispatch request accepted',
                'commandId' => $commandId,
                'status' => 'accepted'
            ], 202);
        } catch (\Exception $e) {
            $this->logger->error("Erro ao solicitar despacho: " . $e->getMessage(), ['occurrence_id' => $occurrenceId]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
