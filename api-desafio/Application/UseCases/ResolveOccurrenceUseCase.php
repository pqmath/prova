<?php

namespace Application\UseCases;

use Domain\Repositories\AuditLogRepositoryInterface;
use Domain\Repositories\OccurrenceRepositoryInterface;
use Domain\Enums\OccurrenceStatus;
use Domain\Entities\Occurrence;
use Domain\Factories\OccurrenceFactory;
use Domain\Services\MessageBrokerInterface;
use Exception;

class ResolveOccurrenceUseCase
{
    public function __construct(
        private readonly OccurrenceRepositoryInterface $repository,
        private readonly OccurrenceFactory $factory,
        private readonly MessageBrokerInterface $broker,
        private readonly AuditLogRepositoryInterface $auditLogRepository
    ) {
    }

    public function execute(string $id, string $source = 'Sistema'): Occurrence
    {
        $occurrence = $this->repository->findByIdForUpdate($id);

        if (!$occurrence) {
            throw new Exception("Occurrence not found");
        }

        if ($occurrence->status === OccurrenceStatus::RESOLVED || $occurrence->status === OccurrenceStatus::CANCELLED) {
            throw new Exception("Occurrence is already resolved or cancelled");
        }

        $updatedOccurrence = $this->factory->reconstitute(
            $occurrence->id,
            $occurrence->externalId,
            $occurrence->type->value,
            OccurrenceStatus::RESOLVED->value,
            $occurrence->description,
            $occurrence->reportedAt->format('Y-m-d H:i:s'),
            $occurrence->createdAt->format('Y-m-d H:i:s'),
            date('Y-m-d H:i:s')
        );

        $this->repository->save($updatedOccurrence);

        $this->auditLogRepository->log(
            'Occurrence',
            $occurrence->id,
            'resolved',
            $source,
            (array) $occurrence,
            (array) $updatedOccurrence
        );

        $this->broker->publish('events', 'occurrence.resolved', [
            'event' => 'occurrence_resolved',
            'data' => [
                'id' => $updatedOccurrence->id,
                'external_id' => $updatedOccurrence->externalId,
                'status' => $updatedOccurrence->status->value,
                'updated_at' => $updatedOccurrence->updatedAt->format('Y-m-d H:i:s'),
            ]
        ]);

        return $updatedOccurrence;
    }
}
