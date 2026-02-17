<?php

namespace Tests\Unit\Application\UseCases;

use Application\UseCases\StartOccurrenceUseCase;
use Domain\Entities\Occurrence;
use Domain\Enums\OccurrenceStatus;
use Domain\Factories\OccurrenceFactory;
use Domain\Repositories\AuditLogRepositoryInterface;
use Domain\Repositories\OccurrenceRepositoryInterface;
use Domain\Services\MessageBrokerInterface;
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
            ->method('findByIdForUpdate')
            ->willReturn($existing);

        $repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Occurrence $occ) {
                return $occ->status === OccurrenceStatus::IN_PROGRESS;
            }));


        $broker = $this->createMock(MessageBrokerInterface::class);
        $broker->expects($this->once())
            ->method('publish')
            ->with(
                'events',
                'occurrence.started',
                $this->callback(function ($payload) use ($existing) {
                    return $payload['event'] === 'occurrence_started' &&
                        $payload['data']['id'] === $existing->id;
                })
            );

        $auditLogRepository = $this->createMock(AuditLogRepositoryInterface::class);
        $auditLogRepository->expects($this->once())
            ->method('log')
            ->with(
                'Occurrence',
                $existing->id,
                'started',
                'Sistema',
                $this->callback(function ($before) use ($existing) {
                    return $before['status'] === OccurrenceStatus::REPORTED;
                }),
                $this->callback(function ($after) {
                    return $after['status'] === OccurrenceStatus::IN_PROGRESS;
                })
            );

        $useCase = new StartOccurrenceUseCase($repository, $factory, $broker, $auditLogRepository);
        $result = $useCase->execute($existing->id);

        $this->assertEquals(OccurrenceStatus::IN_PROGRESS, $result->status);
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
            ->method('findByIdForUpdate')
            ->willReturn($existing);

        $broker = $this->createMock(MessageBrokerInterface::class);
        $auditLogRepository = $this->createMock(AuditLogRepositoryInterface::class);
        $useCase = new StartOccurrenceUseCase($repository, $factory, $broker, $auditLogRepository);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not in reported status');

        $useCase->execute('wd-123');
    }
    public function test_cannot_start_if_occurrence_not_found()
    {
        $repository = $this->createMock(OccurrenceRepositoryInterface::class);
        $factory = new OccurrenceFactory();

        $repository->expects($this->once())
            ->method('findByIdForUpdate')
            ->with('non-existent-id')
            ->willReturn(null);

        $broker = $this->createMock(MessageBrokerInterface::class);
        $auditLogRepository = $this->createMock(AuditLogRepositoryInterface::class);
        $useCase = new StartOccurrenceUseCase($repository, $factory, $broker, $auditLogRepository);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Occurrence not found');

        $useCase->execute('non-existent-id');
    }
}
