<?php

namespace Infrastructure\Repositories;

use Application\Models\Occurrence as EloquentOccurrence;
use Domain\Entities\Occurrence;
use Domain\Repositories\OccurrenceRepositoryInterface;
use Domain\Factories\OccurrenceFactory;

class OccurrenceRepository implements OccurrenceRepositoryInterface
{
    public function __construct(
        private OccurrenceFactory $factory
    ) {
    }

    public function save(Occurrence $occurrence): void
    {
        EloquentOccurrence::updateOrCreate(
            ['id' => $occurrence->id],
            [
                'external_id' => $occurrence->externalId,
                'type' => $occurrence->type->value,
                'status' => $occurrence->status->value,
                'description' => $occurrence->description,
                'reported_at' => $occurrence->reportedAt,
                'created_at' => $occurrence->createdAt,
                'updated_at' => $occurrence->updatedAt,
            ]
        );
    }

    public function findByExternalId(string $externalId): ?Occurrence
    {
        $model = EloquentOccurrence::where('external_id', $externalId)->first();

        if (!$model) {
            return null;
        }

        return $this->factory->reconstitute(
            $model->id,
            $model->external_id,
            $model->type,
            $model->status,
            $model->description,
            $model->reported_at->format('Y-m-d H:i:s'),
            $model->created_at->format('Y-m-d H:i:s'),
            $model->updated_at->format('Y-m-d H:i:s')
        );
    }

    public function findById(string $id): ?Occurrence
    {
        $model = EloquentOccurrence::find($id);

        if (!$model) {
            return null;
        }

        return $this->factory->reconstitute(
            $model->id,
            $model->external_id,
            $model->type,
            $model->status,
            $model->description,
            $model->reported_at->format('Y-m-d H:i:s'),
            $model->created_at->format('Y-m-d H:i:s'),
            $model->updated_at->format('Y-m-d H:i:s')
        );
    }

    public function list(array $filters): array
    {
        $query = EloquentOccurrence::with('dispatches');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['search'])) {
            $query->where('description', 'ilike', '%' . $filters['search'] . '%');
        }

        return $query->orderBy('reported_at', 'desc')->paginate(15)->toArray();
    }
}
