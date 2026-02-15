<?php

namespace Application\UseCases;

use Domain\Repositories\OccurrenceRepositoryInterface;
use Application\Models\AuditLog;
use Domain\Enums\OccurrenceStatus;
use Domain\Entities\Occurrence;
use Domain\Factories\OccurrenceFactory;
use Exception;

class StartOccurrenceUseCase
{
    public function __construct(
        private readonly OccurrenceRepositoryInterface $repository,
        private readonly OccurrenceFactory $factory
    ) {
    }

    public function execute(string $id): Occurrence
    {
        $occurrence = $this->repository->findById($id);

        if (!$occurrence) {
            throw new Exception("Occurrence not found");
        }

        if ($occurrence->status !== OccurrenceStatus::REPORTED) {
            throw new Exception("Occurrence is not in reported status");
        }

        $updatedOccurrence = $this->factory->reconstitute(
            $occurrence->id,
            $occurrence->externalId,
            $occurrence->type->value,
            OccurrenceStatus::IN_PROGRESS->value,
            $occurrence->description,
            $occurrence->reportedAt->format('Y-m-d H:i:s'),
            $occurrence->createdAt->format('Y-m-d H:i:s'),
            date('Y-m-d H:i:s')
        );

        $this->repository->save($updatedOccurrence);

        AuditLog::create([
            'entity_type' => 'Occurrence',
            'entity_id' => $occurrence->id,
            'action' => 'started',
            'before' => (array) $occurrence,
            'after' => (array) $updatedOccurrence,
        ]);

        return $updatedOccurrence;
    }
}
