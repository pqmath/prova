<?php

namespace Domain\Repositories;

interface AuditLogRepositoryInterface
{
    public function log(string $entityType, string $entityId, string $action, string $source, ?array $before, ?array $after, ?array $meta = null): void;
}
