<?php

namespace Application\UseCases;

use Application\Models\Occurrence;
class ListOccurrencesUseCase
{
    public function execute(array $filters): array
    {
        $query = Occurrence::query();

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
