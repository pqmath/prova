<?php

namespace Application\UseCases;

use Application\Models\AuditLog;

class ListAuditLogsUseCase
{
    public function execute(int $perPage = 20): array
    {
        return AuditLog::orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->toArray();
    }
}
