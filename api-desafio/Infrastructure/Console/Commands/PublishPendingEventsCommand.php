<?php

namespace Infrastructure\Console\Commands;

use Domain\Services\LoggerInterface;
use Illuminate\Console\Command;
use Domain\Repositories\EventInboxRepositoryInterface;
use Domain\Services\MessageBrokerInterface;

class PublishPendingEventsCommand extends Command
{
    protected $signature = 'events:publish-pending';
    protected $description = 'Publishes pending events from EventInbox to MessageBroker (Outbox Pattern).';

    public function __construct(
        private readonly EventInboxRepositoryInterface $repository,
        private readonly MessageBrokerInterface $broker,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $events = $this->repository->findPending(50);
        $count = count($events);

        if ($count === 0) {
            return 0;
        }

        $this->info("Found {$count} pending events. Publishing...");
        $this->logger->info("Found {$count} pending events. Publishing...");

        foreach ($events as $event) {
            try {
                $this->broker->publish(
                    'occurrences',
                    $event->type,
                    [
                        'idempotency_key' => $event->idempotencyKey,
                        'payload' => $event->payload,
                        'source' => $event->source,
                        'event_inbox_id' => $event->id
                    ]
                );

                $this->repository->updateStatus($event->id, 'published');
                $this->info("Published event: {$event->id}");
                $this->logger->info("Published event: {$event->id} to 'occurrences' exchange.");
            } catch (\Throwable $e) {
                $this->error("Failed to publish event {$event->id}: " . $e->getMessage());
                $this->logger->error("Failed to publish event {$event->id}: " . $e->getMessage(), ['exception' => $e]);
                $this->repository->incrementAttempts($event->id);
            }
        }

        return 0;
    }
}
