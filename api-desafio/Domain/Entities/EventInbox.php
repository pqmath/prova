<?php

namespace Domain\Entities;

use DateTimeImmutable;

class EventInbox
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $idempotencyKey,
        public readonly string $source,
        public readonly string $type,
        public readonly array $payload,
        public readonly string $status,
        public readonly ?DateTimeImmutable $processedAt,
        public readonly ?string $error,
        public readonly int $publishAttempts,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt
    ) {
    }

    public static function create(
        string $idempotencyKey,
        string $source,
        string $type,
        array $payload
    ): self {
        return new self(
            null,
            $idempotencyKey,
            $source,
            $type,
            $payload,
            'pending',
            null,
            null,
            0,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
    }
}
