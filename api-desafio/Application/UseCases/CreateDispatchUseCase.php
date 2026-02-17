<?php

namespace Application\UseCases;

use Domain\Entities\Dispatch;
use Domain\Repositories\AuditLogRepositoryInterface;
use Domain\Repositories\DispatchRepositoryInterface;
use Domain\Repositories\OccurrenceRepositoryInterface;
use Exception;

class CreateDispatchUseCase
{
    public function __construct(
        private readonly DispatchRepositoryInterface $dispatchRepository,
        private readonly OccurrenceRepositoryInterface $occurrenceRepository,
        private readonly AuditLogRepositoryInterface $auditLogRepository
    ) {
    }

    public function execute(string $occurrenceId, string $resourceCode, string $source = 'Sistema'): Dispatch
    {
        $occurrence = $this->occurrenceRepository->findById($occurrenceId);

        if (!$occurrence) {
            throw new Exception("Occurrence not found");
        }

        $dispatch = Dispatch::create($occurrenceId, $resourceCode);
        $this->dispatchRepository->save($dispatch);

        $this->auditLogRepository->log(
            'Dispatch',
            $dispatch->id,
            'created',
            $source,
            null,
            (array) $dispatch,
            ['occurrence_id' => $occurrenceId]
        );

        return $dispatch;
    }
}
