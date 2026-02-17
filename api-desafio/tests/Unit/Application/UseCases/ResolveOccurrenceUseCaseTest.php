<?php

namespace Tests\Unit\Application\UseCases;

use Application\UseCases\ResolveOccurrenceUseCase;
use Domain\Entities\Occurrence;
use Domain\Enums\OccurrenceStatus;
use Domain\Factories\OccurrenceFactory;
use Domain\Repositories\AuditLogRepositoryInterface;
use Domain\Repositories\OccurrenceRepositoryInterface;
use Domain\Services\MessageBrokerInterface;
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
            ->method('findByIdForUpdate')
            ->willReturn($existing);

        $repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Occurrence $occ) {
                return $occ->status === OccurrenceStatus::RESOLVED;
            }));


        $broker = $this->createMock(MessageBrokerInterface::class);
        $broker->expects($this->once())
            ->method('publish')
            ->with(
                'events',
                'occurrence.resolved',
                $this->callback(function ($payload) use ($existing) {
                    return $payload['event'] === 'occurrence_resolved' &&
                        $payload['data']['id'] === $existing->id;
                })
            );

        $auditLogRepository = $this->createMock(AuditLogRepositoryInterface::class);
        $auditLogRepository->expects($this->once())
            ->method('log')
            ->with(
                'Occurrence',
                $existing->id,
                'resolved',
                'Sistema',
                $this->callback(function ($before) use ($existing) {
                    return $before['status'] === OccurrenceStatus::IN_PROGRESS;
                }),
                $this->callback(function ($after) {
                    return $after['status'] === OccurrenceStatus::RESOLVED;
                })
            );

        $useCase = new ResolveOccurrenceUseCase($repository, $factory, $broker, $auditLogRepository);
        $result = $useCase->execute('wd-123');

        $this->assertEquals(OccurrenceStatus::RESOLVED, $result->status);
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
            ->method('findByIdForUpdate')
            ->willReturn($existing);

        $broker = $this->createMock(MessageBrokerInterface::class);
        $auditLogRepository = $this->createMock(AuditLogRepositoryInterface::class);
        $useCase = new ResolveOccurrenceUseCase($repository, $factory, $broker, $auditLogRepository);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already resolved');

        $useCase->execute('wd-123');
    }
    public function test_cannot_resolve_if_occurrence_not_found()
    {
        $repository = $this->createMock(OccurrenceRepositoryInterface::class);
        $factory = new OccurrenceFactory();

        $repository->expects($this->once())
            ->method('findByIdForUpdate')
            ->with('non-existent-id')
            ->willReturn(null);

        $broker = $this->createMock(MessageBrokerInterface::class);
        $auditLogRepository = $this->createMock(AuditLogRepositoryInterface::class);
        $useCase = new ResolveOccurrenceUseCase($repository, $factory, $broker, $auditLogRepository);

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
            ->method('findByIdForUpdate')
            ->willReturn($existing);

        $broker = $this->createMock(MessageBrokerInterface::class);
        $auditLogRepository = $this->createMock(AuditLogRepositoryInterface::class);
        $useCase = new ResolveOccurrenceUseCase($repository, $factory, $broker, $auditLogRepository);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already resolved or cancelled');

        $useCase->execute('wd-123');
    }
}
