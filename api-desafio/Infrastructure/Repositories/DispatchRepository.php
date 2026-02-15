<?php

namespace Infrastructure\Repositories;

use Application\Models\Dispatch as EloquentDispatch;
use Domain\Entities\Dispatch;
use Domain\Repositories\DispatchRepositoryInterface;

class DispatchRepository implements DispatchRepositoryInterface
{
    public function save(Dispatch $dispatch): void
    {
        EloquentDispatch::updateOrCreate(
            ['id' => $dispatch->id],
            [
                'occurrence_id' => $dispatch->occurrenceId,
                'resource_code' => $dispatch->resourceCode,
                'status' => $dispatch->status->value,
                'created_at' => $dispatch->createdAt,
                'updated_at' => $dispatch->updatedAt,
            ]
        );
    }
}
