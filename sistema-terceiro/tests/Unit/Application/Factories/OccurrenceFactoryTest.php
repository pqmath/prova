<?php

namespace tests\Unit\Application\Factories;

use Application\Factories\OccurrenceFactory;
use Domain\Entities\Occurrence;
use Domain\ValueObjects\OccurrenceType;
use PHPUnit\Framework\TestCase;

class OccurrenceFactoryTest extends TestCase
{
    private OccurrenceFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new OccurrenceFactory();
    }

    public function test_create_random_returns_occurrence()
    {
        $occurrence = $this->factory->createRandom();

        $this->assertInstanceOf(Occurrence::class, $occurrence);
        $this->assertNotEmpty($occurrence->getExternalId());
        $this->assertNotEmpty($occurrence->getDescription());
    }

    public function test_create_with_type_returns_specific_type()
    {
        $occurrence = $this->factory->createWithType('incendio_urbano');

        $this->assertInstanceOf(Occurrence::class, $occurrence);
        $this->assertEquals(OccurrenceType::IncendioUrbano, $occurrence->getType());
    }

    public function test_create_returns_occurrence_with_given_data()
    {
        $occurrence = $this->factory->create('EXT-999', 'resgate_veicular', 'Teste');

        $this->assertEquals('EXT-999', $occurrence->getExternalId());
        $this->assertEquals(OccurrenceType::ResgateVeicular, $occurrence->getType());
        $this->assertEquals('Teste', $occurrence->getDescription());
    }

    public function test_create_with_type_works_for_all_enum_types()
    {
        foreach (OccurrenceType::cases() as $type) {
            $occurrence = $this->factory->createWithType($type->value);
            $this->assertEquals($type, $occurrence->getType());
            $this->assertNotEmpty($occurrence->getDescription());
            $this->assertNotEquals('Ocorrência simulada', $occurrence->getDescription(), "Description for $type->value missing in factory.");
        }
    }
}
