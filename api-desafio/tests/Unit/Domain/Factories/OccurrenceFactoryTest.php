<?php

namespace Tests\Unit\Domain\Factories;

use Domain\Factories\OccurrenceFactory;
use Domain\Entities\Occurrence;
use Domain\Enums\OccurrenceType;
use Domain\Enums\OccurrenceStatus;
use PHPUnit\Framework\TestCase;

class OccurrenceFactoryTest extends TestCase
{
    public function test_create_returns_occurrence()
    {
        $factory = new OccurrenceFactory();
        $occurrence = $factory->create('EXT-1', 'incendio_urbano', 'description', '2023-01-01 12:00:00');

        $this->assertInstanceOf(Occurrence::class, $occurrence);
        $this->assertEquals('EXT-1', $occurrence->externalId);
        $this->assertEquals(OccurrenceType::FIRE, $occurrence->type);
        $this->assertEquals('description', $occurrence->description);
    }

    public function test_reconstitute_returns_occurrence()
    {
        $factory = new OccurrenceFactory();
        $occurrence = $factory->reconstitute(
            'uuid',
            'EXT-2',
            'incendio_urbano',
            'reported',
            'desc',
            '2023-01-01 12:00:00',
            '2023-01-01 12:00:00',
            '2023-01-01 12:00:00'
        );

        $this->assertInstanceOf(Occurrence::class, $occurrence);
        $this->assertEquals('uuid', $occurrence->id);
        $this->assertEquals('EXT-2', $occurrence->externalId);
        $this->assertEquals(OccurrenceType::FIRE, $occurrence->type);
        $this->assertEquals(OccurrenceStatus::REPORTED, $occurrence->status);
    }
}
