<?php

namespace Application\Http\Controllers;

use Application\UseCases\ListOccurrencesUseCase;
use Application\UseCases\StartOccurrenceUseCase;
use Application\UseCases\ResolveOccurrenceUseCase;
use Domain\Services\LoggerInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OccurrenceController extends Controller
{
    public function __construct(
        private readonly ListOccurrencesUseCase $listUseCase,
        private readonly StartOccurrenceUseCase $startUseCase,
        private readonly ResolveOccurrenceUseCase $resolveUseCase,
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

    public function start(string $id): JsonResponse
    {
        $this->logger->info("Iniciando atendimento da ocorrência {$id}");
        try {
            $occurrence = $this->startUseCase->execute($id);
            $this->logger->info("Ocorrência {$id} iniciada com sucesso");
            return response()->json([
                'message' => 'Occurrence started successfully',
                'data' => $occurrence
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Erro ao iniciar ocorrência {$id}: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function resolve(string $id): JsonResponse
    {
        $this->logger->info("Resolvendo ocorrência {$id}");
        try {
            $occurrence = $this->resolveUseCase->execute($id);
            $this->logger->info("Ocorrência {$id} resolvida com sucesso");
            return response()->json([
                'message' => 'Occurrence resolved successfully',
                'data' => $occurrence
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Erro ao resolver ocorrência {$id}: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
