<?php

namespace Domain\Entities;

use DateTimeImmutable;

class AuditLog
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $entityType,
        public readonly string $entityId,
        public readonly string $action,
        public readonly ?array $before,
        public readonly ?array $after,
        public readonly ?array $meta,
        public readonly DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(
        string $entityType,
        string $entityId,
        string $action,
        ?array $before = null,
        ?array $after = null,
    ): self {
        return new self(
            null,
            $entityType,
            $entityId,
            $action,
            $before,
            $after,
            null,
            new DateTimeImmutable()
        );
    }
}
