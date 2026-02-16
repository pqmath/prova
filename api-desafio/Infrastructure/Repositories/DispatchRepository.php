<?php

namespace Infrastructure\Repositories;

use Application\Models\Dispatch as EloquentDispatch;
use Domain\Entities\Dispatch;
use Domain\Repositories\DispatchRepositoryInterface;
use Domain\Enums\DispatchStatus;

class DispatchRepository implements DispatchRepositoryInterface
{
    public function findByOccurrenceId(string $occurrenceId): array
    {
        $models = EloquentDispatch::where('occurrence_id', $occurrenceId)->get();

        return $models->map(fn(EloquentDispatch $model) => $this->toDomain($model))->toArray();
    }

    private function toDomain(EloquentDispatch $model): Dispatch
    {
        return new Dispatch(
            id: $model->id,
            occurrenceId: $model->occurrence_id,
            resourceCode: $model->resource_code,
            status: DispatchStatus::from($model->status),
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }

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
