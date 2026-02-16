<?php

namespace Tests\Unit\Application\UseCases;

use Application\Models\EventInbox;
use Application\UseCases\RequestCommandUseCase;
use Domain\Services\LoggerInterface;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestCommandUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_event_inbox()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info');

        $useCase = new RequestCommandUseCase($logger);

        $id = $useCase->execute('key-123', 'ext-system', 'event.type', ['data' => 1]);

        $this->assertNotEmpty($id);
        $this->assertDatabaseHas('event_inboxes', [
            'id' => $id,
            'idempotency_key' => 'key-123',
            'status' => 'pending'
        ]);
    }

    public function test_handles_duplicate_key_gracefully()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');
        $logger->expects($this->any())->method('info');

        $useCase = new RequestCommandUseCase($logger);

        $id1 = $useCase->execute('key-duplicate', 'source-A', 'type-A', []);
        $id2 = $useCase->execute('key-duplicate', 'source-A', 'type-A', []);
        $this->assertEquals($id1, $id2);

        $this->assertEquals(1, EventInbox::where('idempotency_key', 'key-duplicate')->count());
    }
}
