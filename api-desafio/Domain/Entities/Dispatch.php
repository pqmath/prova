<?php

namespace Domain\Entities;

use Domain\Enums\DispatchStatus;
use Illuminate\Support\Str;
use DateTimeImmutable;

class Dispatch
{
    public function __construct(
        public readonly string $id,
        public readonly string $occurrenceId,
        public readonly string $resourceCode,
        public readonly DispatchStatus $status,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(string $occurrenceId, string $resourceCode): self
    {
        return new self(
            Str::uuid()->toString(),
            $occurrenceId,
            $resourceCode,
            DispatchStatus::ASSIGNED,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
    }
}
