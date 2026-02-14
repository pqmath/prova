<?php

namespace Domain\Entities;

use Domain\Enums\OccurrenceStatus;
use Domain\Enums\OccurrenceType;
use Illuminate\Support\Str;
use DateTimeImmutable;

class Occurrence
{
    public function __construct(
        public readonly string $id,
        public readonly string $externalId,
        public readonly OccurrenceType $type,
        public readonly OccurrenceStatus $status,
        public readonly string $description,
        public readonly DateTimeImmutable $reportedAt,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        string $externalId,
        OccurrenceType $type,
        string $description,
        DateTimeImmutable $reportedAt
    ): self {
        return new self(
            Str::uuid()->toString(),
            $externalId,
            $type,
            OccurrenceStatus::REPORTED,
            $description,
            $reportedAt,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
    }
}
