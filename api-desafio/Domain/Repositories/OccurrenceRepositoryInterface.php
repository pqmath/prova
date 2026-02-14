<?php

namespace Domain\Repositories;

use Domain\Entities\Occurrence;

interface OccurrenceRepositoryInterface
{
    public function save(Occurrence $occurrence): void;
    public function findByExternalId(string $externalId): ?Occurrence;
}
