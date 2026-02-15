<?php

namespace Application\UseCases;

use Domain\Entities\Dispatch;
use Domain\Repositories\DispatchRepositoryInterface;
use Domain\Repositories\OccurrenceRepositoryInterface;
use Application\Models\AuditLog;
use Exception;

class CreateDispatchUseCase
{
    public function __construct(
        private readonly DispatchRepositoryInterface $dispatchRepository,
        private readonly OccurrenceRepositoryInterface $occurrenceRepository
    ) {
    }

    public function execute(string $occurrenceId, string $resourceCode): Dispatch
    {
        $occurrence = $this->occurrenceRepository->findById($occurrenceId);

        if (!$occurrence) {
            throw new Exception("Occurrence not found");
        }

        $dispatch = Dispatch::create($occurrenceId, $resourceCode);
        $this->dispatchRepository->save($dispatch);

        AuditLog::create([
            'entity_type' => 'Dispatch',
            'entity_id' => $dispatch->id,
            'action' => 'created',
            'after' => (array) $dispatch,
            'meta' => ['occurrence_id' => $occurrenceId]
        ]);

        return $dispatch;
    }
}
