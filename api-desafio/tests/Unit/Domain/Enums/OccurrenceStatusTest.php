<?php

namespace Tests\Unit\Domain\Enums;

use Domain\Enums\OccurrenceStatus;
use PHPUnit\Framework\TestCase;

class OccurrenceStatusTest extends TestCase
{
    public function test_enum_values()
    {
        $this->assertEquals('reported', OccurrenceStatus::REPORTED->value);
        $this->assertEquals('in_progress', OccurrenceStatus::IN_PROGRESS->value);
        $this->assertEquals('resolved', OccurrenceStatus::RESOLVED->value);
        $this->assertEquals('cancelled', OccurrenceStatus::CANCELLED->value);
    }
}
