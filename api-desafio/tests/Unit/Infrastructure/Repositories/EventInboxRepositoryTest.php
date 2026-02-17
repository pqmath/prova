<?php

namespace Tests\Unit\Infrastructure\Repositories;

use Application\Models\EventInbox;
use Domain\Entities\EventInbox as DomainEventInbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Repositories\EventInboxRepository;
use Tests\TestCase;

class EventInboxRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EventInboxRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EventInboxRepository();
    }

    public function test_can_save_event_inbox()
    {
        $event = DomainEventInbox::create('key-repo-1', 'source-repo', 'type.test', ['data' => 1]);

        $this->repository->save($event);

        $this->assertDatabaseHas('event_inboxes', [
            'idempotency_key' => 'key-repo-1',
            'source' => 'source-repo',
            'type' => 'type.test'
        ]);
    }

    public function test_can_find_by_idempotency_key()
    {
        $event = DomainEventInbox::create('key-repo-2', 'source-repo', 'type.test', ['data' => 1]);
        $this->repository->save($event);

        $retrieved = $this->repository->findByIdempotencyKey('key-repo-2');

        $this->assertNotNull($retrieved);
        $this->assertEquals('key-repo-2', $retrieved->idempotencyKey);
    }

    public function test_find_by_idempotency_key_returns_null_when_not_found()
    {
        $retrieved = $this->repository->findByIdempotencyKey('non-existent-key');

        $this->assertNull($retrieved);
    }

    public function test_find_pending_returns_only_pending_events()
    {
        $this->createEvent('key-pending-1', 'pending');
        $this->createEvent('key-processed-1', 'processed');
        $this->createEvent('key-pending-2', 'pending');

        $pending = $this->repository->findPending();

        $this->assertCount(2, $pending);
        $this->assertEquals('key-pending-1', $pending[0]->idempotencyKey);
        $this->assertEquals('key-pending-2', $pending[1]->idempotencyKey);
    }

    public function test_update_status()
    {
        $id = $this->createEvent('key-status-1', 'pending');

        $this->repository->updateStatus($id, 'processed');

        $this->assertDatabaseHas('event_inboxes', [
            'id' => $id,
            'status' => 'processed'
        ]);
    }

    public function test_increment_attempts()
    {
        $id = $this->createEvent('key-attempts-1', 'pending');

        $this->repository->incrementAttempts($id);
        $this->repository->incrementAttempts($id);

        $this->assertDatabaseHas('event_inboxes', [
            'id' => $id,
            'publish_attempts' => 2
        ]);
    }

    private function createEvent(string $key, string $status): string
    {
        $model = EventInbox::create([
            'idempotency_key' => $key,
            'source' => 'test',
            'type' => 'test',
            'payload' => [],
            'status' => $status,
            'publish_attempts' => 0
        ]);
        return $model->id;
    }
}
