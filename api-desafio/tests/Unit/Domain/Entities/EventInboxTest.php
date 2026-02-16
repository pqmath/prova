<?php

namespace Tests\Unit\Domain\Entities;

use Domain\Entities\EventInbox;
use PHPUnit\Framework\TestCase;

class EventInboxTest extends TestCase
{
    public function test_can_create_event_inbox_with_valid_data()
    {
        $idempotencyKey = 'key-123';
        $source = 'external-system';
        $type = 'occurrence.created';
        $payload = ['foo' => 'bar'];

        $event = EventInbox::create($idempotencyKey, $source, $type, $payload);

        $this->assertInstanceOf(EventInbox::class, $event);
        $this->assertNull($event->id);
        $this->assertEquals($idempotencyKey, $event->idempotencyKey);
        $this->assertEquals($source, $event->source);
        $this->assertEquals($type, $event->type);
        $this->assertEquals($payload, $event->payload);
        $this->assertEquals('pending', $event->status);
        $this->assertEquals(0, $event->publishAttempts);
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->createdAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->updatedAt);
    }
}
