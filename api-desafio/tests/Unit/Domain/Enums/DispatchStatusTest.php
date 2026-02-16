<?php

namespace Tests\Unit\Domain\Enums;

use Domain\Enums\DispatchStatus;
use PHPUnit\Framework\TestCase;

class DispatchStatusTest extends TestCase
{
    public function test_enum_values()
    {
        $this->assertEquals('assigned', DispatchStatus::ASSIGNED->value);
        $this->assertEquals('en_route', DispatchStatus::EN_ROUTE->value);
        $this->assertEquals('on_site', DispatchStatus::ON_SITE->value);
        $this->assertEquals('closed', DispatchStatus::CLOSED->value);
    }
}
