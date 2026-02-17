<?php

namespace Domain\Factories;

use Domain\Entities\AuditLog;

class AuditLogFactory
{
    public function create(
        string $entityType,
        string $entityId,
        string $action,
        ?array $changes = null
    ): AuditLog {
        return AuditLog::create($entityType, $entityId, $action, $changes);
    }
}
