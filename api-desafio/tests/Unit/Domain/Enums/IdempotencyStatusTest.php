<?php

namespace Tests\Unit\Domain\Enums;

use Domain\Enums\IdempotencyStatus;
use PHPUnit\Framework\TestCase;

class IdempotencyStatusTest extends TestCase
{
    public function test_enum_values()
    {
        $this->assertEquals('pending', IdempotencyStatus::PENDING->value);
        $this->assertEquals('processed', IdempotencyStatus::PROCESSED->value);
        $this->assertEquals('failed', IdempotencyStatus::FAILED->value);
    }
}
