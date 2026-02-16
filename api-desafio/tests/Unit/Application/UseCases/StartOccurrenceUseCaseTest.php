<?php

namespace Tests\Unit\Application\UseCases;

use Application\Models\AuditLog;
use Application\UseCases\StartOccurrenceUseCase;
use Domain\Entities\Occurrence;
use Domain\Enums\OccurrenceStatus;
use Domain\Enums\OccurrenceType;
use Domain\Factories\OccurrenceFactory;
use Domain\Repositories\OccurrenceRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartOccurrenceUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_start_occurrence()
    {
        $repository = $this->createMock(OccurrenceRepositoryInterface::class);
        $factory = new OccurrenceFactory();

        $existing = $factory->create('EXT-123', 'incendio_urbano', 'Description', '2026-02-01 10:00:00');

        $repository->expects($this->once())
            ->method('findById')
            ->willReturn($existing);

        $repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Occurrence $occ) {
                return $occ->status === OccurrenceStatus::IN_PROGRESS;
            }));

        $useCase = new StartOccurrenceUseCase($repository, $factory);
        $result = $useCase->execute($existing->id);

        $this->assertEquals(OccurrenceStatus::IN_PROGRESS, $result->status);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'Occurrence',
            'action' => 'started',
        ]);
    }

    public function test_cannot_start_if_not_reported()
    {
        $repository = $this->createMock(OccurrenceRepositoryInterface::class);
        $factory = new OccurrenceFactory();

        $existing = $factory->reconstitute(
            'wd-123',
            'EXT-123',
            'incendio_urbano',
            'in_progress',
            'Desc',
            '2026-01-01',
            '2026-01-01',
            '2026-01-01'
        );

        $repository->expects($this->once())
            ->method('findById')
            ->willReturn($existing);

        $useCase = new StartOccurrenceUseCase($repository, $factory);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not in reported status');

        $useCase->execute('wd-123');
    }
    public function test_cannot_start_if_occurrence_not_found()
    {
        $repository = $this->createMock(OccurrenceRepositoryInterface::class);
        $factory = new OccurrenceFactory();

        $repository->expects($this->once())
            ->method('findById')
            ->with('non-existent-id')
            ->willReturn(null);

        $useCase = new StartOccurrenceUseCase($repository, $factory);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Occurrence not found');

        $useCase->execute('non-existent-id');
    }
}
