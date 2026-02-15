<?php

namespace Application\UseCases;

use Application\Models\EventInbox;
use Domain\Services\MessageBrokerInterface;

class RequestOccurrenceCreationUseCase
{
    public function __construct(
        private readonly MessageBrokerInterface $broker
    ) {
    }

    public function execute(string $idempotencyKey, string $source, string $type, array $payload): string
    {

        $existing = EventInbox::where('idempotency_key', $idempotencyKey)
            ->where('source', $source)
            ->first();

        if ($existing) {
            return $existing->id;
        }

        $event = EventInbox::create([
            'idempotency_key' => $idempotencyKey,
            'source' => $source,
            'type' => $type,
            'payload' => $payload,
            'status' => 'pending',
            'publish_attempts' => 0,
        ]);

        $this->broker->publish('occurrences', $type, [
            'event_inbox_id' => $event->id,
            'payload' => $payload
        ]);

        return $event->id;
    }
}
