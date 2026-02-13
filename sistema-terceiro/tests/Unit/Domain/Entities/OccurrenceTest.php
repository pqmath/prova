<?php

namespace tests\Unit\Domain\Entities;

use Domain\Entities\Occurrence;
use Domain\ValueObjects\OccurrenceType;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class OccurrenceTest extends TestCase
{
    public function test_can_create_valid_occurrence()
    {
        $occurrence = new Occurrence(
            'EXT-123',
            OccurrenceType::IncendioUrbano,
            'Descrição teste',
            new \DateTimeImmutable()
        );

        $this->assertEquals('EXT-123', $occurrence->getExternalId());
        $this->assertEquals(OccurrenceType::IncendioUrbano, $occurrence->getType());
        $this->assertEquals('Descrição teste', $occurrence->getDescription());
    }

    public function test_cannot_create_occurrence_with_empty_external_id()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('External ID não pode ser vazio');

        new Occurrence(
            '',
            OccurrenceType::IncendioUrbano,
            'Descrição teste',
            new \DateTimeImmutable()
        );
    }

    public function test_cannot_create_occurrence_with_long_external_id()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('External ID não pode ter mais de 100 caracteres');

        new Occurrence(
            str_repeat('a', 101),
            OccurrenceType::IncendioUrbano,
            'Descrição teste',
            new \DateTimeImmutable()
        );
    }

    public function test_cannot_create_occurrence_with_empty_description()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Descrição não pode ser vazia');

        new Occurrence(
            'EXT-123',
            OccurrenceType::IncendioUrbano,
            '',
            new \DateTimeImmutable()
        );
    }

    public function test_cannot_create_occurrence_with_long_description()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Descrição não pode ter mais de 500 caracteres');

        new Occurrence(
            'EXT-123',
            OccurrenceType::IncendioUrbano,
            str_repeat('a', 501),
            new \DateTimeImmutable()
        );
    }

    public function test_to_array_returns_correct_structure()
    {
        $date = new \DateTimeImmutable('2026-01-01 12:00:00');
        $occurrence = new Occurrence(
            'EXT-123',
            OccurrenceType::ResgateVeicular,
            'Acidente',
            $date
        );

        $array = $occurrence->toArray();

        $this->assertEquals('EXT-123', $array['externalId']);
        $this->assertEquals('resgate_veicular', $array['type']);
        $this->assertEquals('Acidente', $array['description']);
        $this->assertEquals($date->format('c'), $array['reportedAt']);
    }

    public function test_with_updated_description_creates_new_instance()
    {
        $occurrence = new Occurrence(
            'EXT-123',
            OccurrenceType::IncendioUrbano,
            'Original',
            new \DateTimeImmutable()
        );

        $newOccurrence = $occurrence->withUpdatedDescription('Updated');

        $this->assertNotSame($occurrence, $newOccurrence);
        $this->assertEquals('Original', $occurrence->getDescription());
        $this->assertEquals('Updated', $newOccurrence->getDescription());
        $this->assertEquals($occurrence->getExternalId(), $newOccurrence->getExternalId());
    }

    public function test_has_reported_at()
    {
        $date = new \DateTimeImmutable('2024-01-01 10:00:00');
        $occurrence = new Occurrence(
            'EXT-123',
            OccurrenceType::IncendioUrbano,
            'Original',
            $date
        );

        $this->assertSame($date, $occurrence->getReportedAt());
    }
}
