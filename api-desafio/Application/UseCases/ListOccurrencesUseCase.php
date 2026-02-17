<?php

namespace Application\UseCases;

use Domain\Repositories\OccurrenceRepositoryInterface;

class ListOccurrencesUseCase
{
    public function __construct(
        private readonly OccurrenceRepositoryInterface $repository
    ) {
    }

    public function execute(array $filters): array
    {
        return $this->repository->list($filters);
    }
}
