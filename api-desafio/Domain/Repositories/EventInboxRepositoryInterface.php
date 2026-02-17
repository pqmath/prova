<?php

namespace Domain\Repositories;

use Domain\Entities\EventInbox;

interface EventInboxRepositoryInterface
{
    public function save(EventInbox $event): void;
    public function findByIdempotencyKey(string $key): ?EventInbox;
    public function findPending(int $limit = 10): array;
    public function updateStatus(string $id, string $status): void;
    public function incrementAttempts(string $id): void;
}
