<?php

namespace Tests\Unit\Domain\Entities;

use Domain\Entities\Dispatch;
use Domain\Enums\DispatchStatus;
use PHPUnit\Framework\TestCase;

class DispatchTest extends TestCase
{
    public function test_can_create_dispatch_with_valid_data()
    {
        $occurrenceId = 'occ-uuid-123';
        $resourceCode = 'ABT-01';

        $dispatch = Dispatch::create($occurrenceId, $resourceCode);

        $this->assertInstanceOf(Dispatch::class, $dispatch);
        $this->assertNotEmpty($dispatch->id);
        $this->assertEquals($occurrenceId, $dispatch->occurrenceId);
        $this->assertEquals($resourceCode, $dispatch->resourceCode);
        $this->assertEquals(DispatchStatus::ASSIGNED, $dispatch->status);
        $this->assertInstanceOf(\DateTimeImmutable::class, $dispatch->createdAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $dispatch->updatedAt);
    }
}
