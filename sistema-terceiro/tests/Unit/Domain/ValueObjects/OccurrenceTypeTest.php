<?php

namespace tests\Unit\Domain\ValueObjects;

use Domain\ValueObjects\OccurrenceType;
use PHPUnit\Framework\TestCase;

class OccurrenceTypeTest extends TestCase
{
    public function test_enum_values()
    {
        $this->assertEquals('incendio_urbano', OccurrenceType::IncendioUrbano->value);
        $this->assertEquals('resgate_veicular', OccurrenceType::ResgateVeicular->value);
    }

    public function test_random_returns_valid_case()
    {
        $random = OccurrenceType::random();
        $this->assertInstanceOf(OccurrenceType::class, $random);
    }

    public function test_all_returns_array_of_strings()
    {
        $all = OccurrenceType::all();
        $this->assertIsArray($all);
        $this->assertContains('incendio_urbano', $all);
        $this->assertCount(4, $all);
    }

    public function test_get_value_method()
    {
        $type = OccurrenceType::IncendioUrbano;
        $this->assertEquals('incendio_urbano', $type->getValue());
    }
}
