<?php

namespace Domain\Factories;

use Domain\Entities\Occurrence;
use Domain\Enums\OccurrenceType;
use Domain\Enums\OccurrenceStatus;
use DateTimeImmutable;

class OccurrenceFactory
{
    public function create(
        string $externalId,
        string $type,
        string $description,
        string $reportedAt
    ): Occurrence {
        return Occurrence::create(
            $externalId,
            OccurrenceType::from($type),
            $description,
            new DateTimeImmutable($reportedAt)
        );
    }

    public function reconstitute(
        string $id,
        string $externalId,
        string $type,
        string $status,
        string $description,
        string $reportedAt,
        string $createdAt,
        string $updatedAt
    ): Occurrence {
        return new Occurrence(
            $id,
            $externalId,
            OccurrenceType::from($type),
            OccurrenceStatus::from($status),
            $description,
            new DateTimeImmutable($reportedAt),
            new DateTimeImmutable($createdAt),
            new DateTimeImmutable($updatedAt)
        );
    }
}
