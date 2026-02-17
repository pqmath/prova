<?php

namespace Tests\Unit\Domain\Factories;

use Domain\Factories\DispatchFactory;
use Domain\Entities\Dispatch;
use PHPUnit\Framework\TestCase;

class DispatchFactoryTest extends TestCase
{
    public function test_create_returns_dispatch()
    {
        $factory = new DispatchFactory();
        $dispatch = $factory->create('occurrence-id', 'resource-code');

        $this->assertInstanceOf(Dispatch::class, $dispatch);
        $this->assertEquals('occurrence-id', $dispatch->occurrenceId);
        $this->assertEquals('resource-code', $dispatch->resourceCode);
    }
}
