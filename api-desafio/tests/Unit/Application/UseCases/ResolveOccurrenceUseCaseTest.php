<?php

namespace Tests\Unit\Application\UseCases;

use Application\Models\AuditLog;
use Application\UseCases\ResolveOccurrenceUseCase;
use Domain\Entities\Occurrence;
use Domain\Enums\OccurrenceStatus;
use Domain\Enums\OccurrenceType;
use Domain\Factories\OccurrenceFactory;
use Domain\Repositories\OccurrenceRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveOccurrenceUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_resolve_occurrence()
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

        $repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Occurrence $occ) {
                return $occ->status === OccurrenceStatus::RESOLVED;
            }));

        $useCase = new ResolveOccurrenceUseCase($repository, $factory);
        $result = $useCase->execute('wd-123');

        $this->assertEquals(OccurrenceStatus::RESOLVED, $result->status);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'Occurrence',
            'action' => 'resolved',
            'entity_id' => 'wd-123'
        ]);
    }

    public function test_cannot_resolve_if_already_resolved()
    {
        $repository = $this->createMock(OccurrenceRepositoryInterface::class);
        $factory = new OccurrenceFactory();

        $existing = $factory->reconstitute(
            'wd-123',
            'EXT-123',
            'incendio_urbano',
            'resolved',
            'Desc',
            '2026-01-01',
            '2026-01-01',
            '2026-01-01'
        );

        $repository->expects($this->once())
            ->method('findById')
            ->willReturn($existing);

        $useCase = new ResolveOccurrenceUseCase($repository, $factory);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already resolved');

        $useCase->execute('wd-123');
    }
    public function test_cannot_resolve_if_occurrence_not_found()
    {
        $repository = $this->createMock(OccurrenceRepositoryInterface::class);
        $factory = new OccurrenceFactory();

        $repository->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $useCase = new ResolveOccurrenceUseCase($repository, $factory);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Occurrence not found');

        $useCase->execute('non-existent-id');
    }

    public function test_cannot_resolve_if_cancelled()
    {
        $repository = $this->createMock(OccurrenceRepositoryInterface::class);
        $factory = new OccurrenceFactory();

        $existing = $factory->reconstitute(
            'wd-123',
            'EXT-123',
            'incendio_urbano',
            'cancelled',
            'Desc',
            '2026-01-01',
            '2026-01-01',
            '2026-01-01'
        );

        $repository->expects($this->once())
            ->method('findById')
            ->willReturn($existing);

        $useCase = new ResolveOccurrenceUseCase($repository, $factory);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already resolved or cancelled');

        $useCase->execute('wd-123');
    }
}
