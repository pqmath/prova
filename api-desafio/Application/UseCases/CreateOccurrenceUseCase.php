<?php

namespace Application\UseCases;

use Application\DTOs\CreateOccurrenceDTO;
use Domain\Factories\OccurrenceFactory;
use Domain\Repositories\OccurrenceRepositoryInterface;
use Domain\Services\MessageBrokerInterface;

class CreateOccurrenceUseCase
{
    public function __construct(
        private readonly OccurrenceFactory $factory,
        private readonly OccurrenceRepositoryInterface $repository,
        private readonly MessageBrokerInterface $broker
    ) {
    }

    public function execute(CreateOccurrenceDTO $dto): array
    {
        $existing = $this->repository->findByExternalId($dto->externalId);

        if ($existing) {
            $occurrence = $this->factory->reconstitute(
                $existing->id,
                $existing->externalId,
                $dto->type,
                $existing->status->value,
                $dto->description,
                $dto->reportedAt,
                $existing->createdAt->format('Y-m-d H:i:s'),
                date('Y-m-d H:i:s')
            );

            $this->repository->save($occurrence);
            $wasCreated = false;
        } else {
            $occurrence = $this->factory->create(
                $dto->externalId,
                $dto->type,
                $dto->description,
                $dto->reportedAt
            );
            $this->repository->save($occurrence);
            $wasCreated = true;

            $this->broker->publish('events', 'occurrence.created', [
                'event' => 'occurrence_created',
                'data' => [
                    'id' => $occurrence->id,
                    'external_id' => $occurrence->externalId,
                    'status' => $occurrence->status->value,
                    'created_at' => $occurrence->createdAt->format('Y-m-d H:i:s'),
                ]
            ]);
        }

        return [
            'occurrence' => $occurrence,
            'action' => $wasCreated ? 'created' : 'updated',
            'before' => $wasCreated ? null : $existing,
            'source' => 'Sistema Terceiro'
        ];
    }
}
