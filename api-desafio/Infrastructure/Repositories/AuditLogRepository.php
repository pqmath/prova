<?php

namespace Infrastructure\Repositories;

use Domain\Repositories\AuditLogRepositoryInterface;
use Application\Models\AuditLog;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function log(string $entityType, string $entityId, string $action, string $source, ?array $before, ?array $after, ?array $meta = null): void
    {
        AuditLog::create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'source' => $source,
            'before' => $before,
            'after' => $after,
            'meta' => $meta,
        ]);
    }
}
