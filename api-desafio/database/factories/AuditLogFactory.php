<?php

namespace Database\Factories;

use Application\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'entity_type' => 'Occurrence',
            'entity_id' => $this->faker->uuid(),
            'action' => 'status_changed',
            'source' => 'Sistema',
            'before' => ['status' => 'reported'],
            'after' => ['status' => 'in_progress'],
            'meta' => [],
        ];
    }
}
