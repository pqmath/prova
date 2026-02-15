<?php

namespace Application\Http\Controllers;

use Application\UseCases\CreateDispatchUseCase;
use Domain\Services\LoggerInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DispatchController extends Controller
{
    public function __construct(
        private readonly CreateDispatchUseCase $createUseCase,
        private readonly LoggerInterface $logger
    ) {
    }

    public function store(Request $request, string $occurrenceId): JsonResponse
    {
        $request->validate([
            'resourceCode' => 'required|string'
        ]);

        $resourceCode = $request->input('resourceCode');
        $this->logger->info("Solicitação de despacho para ocorrência {$occurrenceId}. Recurso: {$resourceCode}");

        try {
            $dispatch = $this->createUseCase->execute(
                $occurrenceId,
                $resourceCode
            );

            $this->logger->info("Despacho criado com sucesso", ['dispatch_id' => $dispatch->id]);

            return response()->json([
                'message' => 'Dispatch created successfully',
                'data' => $dispatch
            ], 201);
        } catch (\Exception $e) {
            $this->logger->error("Erro ao criar despacho: " . $e->getMessage(), ['occurrence_id' => $occurrenceId]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
