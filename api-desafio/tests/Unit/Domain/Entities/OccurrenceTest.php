<?php

namespace Tests\Unit\Domain\Entities;

use Domain\Entities\Occurrence;
use Domain\Enums\OccurrenceStatus;
use Domain\Enums\OccurrenceType;
use PHPUnit\Framework\TestCase;

class OccurrenceTest extends TestCase
{
    public function test_can_create_occurrence_with_valid_data()
    {
        $externalId = 'EXT-123';
        $type = OccurrenceType::FIRE;
        $description = 'Incêndio na rua X';
        $reportedAt = new \DateTimeImmutable('2026-02-01 10:00:00');

        $occurrence = Occurrence::create($externalId, $type, $description, $reportedAt);

        $this->assertInstanceOf(Occurrence::class, $occurrence);
        $this->assertNotEmpty($occurrence->id);
        $this->assertEquals($externalId, $occurrence->externalId);
        $this->assertEquals($type, $occurrence->type);
        $this->assertEquals(OccurrenceStatus::REPORTED, $occurrence->status);
        $this->assertEquals($description, $occurrence->description);
        $this->assertEquals($reportedAt, $occurrence->reportedAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $occurrence->createdAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $occurrence->updatedAt);
    }
}
