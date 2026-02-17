<?php

namespace Domain\Factories;

use Domain\Entities\IdempotencyKey;

class IdempotencyKeyFactory
{
    public function create(string $key, ?string $source, ?array $payload): IdempotencyKey
    {
        return IdempotencyKey::create($key, $source, $payload);
    }
}
