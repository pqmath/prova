<?php

namespace Application\UseCases;

use Application\Models\EventInbox;
use Domain\Services\LoggerInterface;

class RequestCommandUseCase
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(
        string $idempotencyKey,
        string $source,
        string $type,
        array $payload
    ): string {
        $event = EventInbox::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'source' => $source,
                'type' => $type,
                'payload' => $payload,
                'status' => 'pending',
                'publish_attempts' => 0,
            ]
        );

        if ($event->wasRecentlyCreated) {
            $this->logger->info('EventInbox created', [
                'event_inbox_id' => $event->id,
                'idempotency_key' => $idempotencyKey,
                'source' => $source,
                'type' => $type,
            ]);
        } else {
            $this->logger->warning('Duplicate key detected (returned existing)', [
                'idempotency_key' => $idempotencyKey,
                'source' => $source,
                'existing_id' => $event->id
            ]);
        }

        return $event->id;
    }


}
