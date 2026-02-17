<?php

namespace Domain\Entities;

use Domain\Enums\IdempotencyStatus;
use DateTimeImmutable;

class IdempotencyKey
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $key,
        public readonly ?string $source,
        public readonly IdempotencyStatus $status,
        public readonly ?array $requestPayload,
        public readonly ?array $responsePayload,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(string $key, ?string $source, ?array $requestPayload): self
    {
        return new self(
            null,
            $key,
            $source,
            IdempotencyStatus::PENDING,
            $requestPayload,
            null,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
    }
}
