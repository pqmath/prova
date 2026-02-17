<?php

namespace Infrastructure\Repositories;

use Domain\Entities\EventInbox as DomainEventInbox;
use Domain\Repositories\EventInboxRepositoryInterface;
use Application\Models\EventInbox as EloquentEventInbox;
use DateTimeImmutable;

class EventInboxRepository implements EventInboxRepositoryInterface
{
    public function save(DomainEventInbox $event): void
    {
        EloquentEventInbox::create([
            'idempotency_key' => $event->idempotencyKey,
            'source' => $event->source,
            'type' => $event->type,
            'payload' => $event->payload,
            'status' => $event->status,
            'publish_attempts' => $event->publishAttempts,
        ]);
    }

    public function findByIdempotencyKey(string $key): ?DomainEventInbox
    {
        $model = EloquentEventInbox::where('idempotency_key', $key)->first();

        if (!$model) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findPending(int $limit = 10): array
    {
        $models = EloquentEventInbox::where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        return $models->map(fn(EloquentEventInbox $model) => $this->toDomain($model))->toArray();
    }

    public function updateStatus(string $id, string $status): void
    {
        EloquentEventInbox::where('id', $id)->update(['status' => $status]);
    }

    public function incrementAttempts(string $id): void
    {
        EloquentEventInbox::where('id', $id)->increment('publish_attempts');
    }

    private function toDomain(EloquentEventInbox $model): DomainEventInbox
    {
        return new DomainEventInbox(
            $model->id,
            $model->idempotency_key,
            $model->source,
            $model->type,
            $model->payload,
            $model->status,
            $model->processed_at ? new DateTimeImmutable($model->processed_at) : null,
            $model->error,
            $model->publish_attempts,
            new DateTimeImmutable($model->created_at),
            new DateTimeImmutable($model->updated_at)
        );
    }
}
