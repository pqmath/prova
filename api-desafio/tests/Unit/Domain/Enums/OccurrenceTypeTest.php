<?php

namespace Tests\Unit\Domain\Enums;

use Domain\Enums\OccurrenceType;
use PHPUnit\Framework\TestCase;

class OccurrenceTypeTest extends TestCase
{
    public function test_enum_values()
    {
        $this->assertEquals('incendio_urbano', OccurrenceType::FIRE->value);
        $this->assertEquals('resgate_veicular', OccurrenceType::RESCUE->value);
        $this->assertEquals('atendimento_pre_hospitalar', OccurrenceType::MEDICAL->value);
        $this->assertEquals('salvamento_aquatico', OccurrenceType::AQUATIC->value);
        $this->assertEquals('falso_chamado', OccurrenceType::FALSE_ALARM->value);
    }
}
