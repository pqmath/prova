<?php

namespace Tests\Unit\Application\UseCases;

use Application\UseCases\CreateDispatchUseCase;
use Domain\Entities\Dispatch;
use Domain\Factories\OccurrenceFactory;
use Domain\Repositories\AuditLogRepositoryInterface;
use Domain\Repositories\DispatchRepositoryInterface;
use Domain\Repositories\OccurrenceRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateDispatchUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_dispatch()
    {
        $dispatchRepo = $this->createMock(DispatchRepositoryInterface::class);
        $occurrenceRepo = $this->createMock(OccurrenceRepositoryInterface::class);
        $auditLogRepo = $this->createMock(AuditLogRepositoryInterface::class);
        $factory = new OccurrenceFactory();

        $occurrence = $factory->create('EXT-123', 'incendio_urbano', 'Desc', '2026-01-01');

        $occurrenceRepo->expects($this->once())
            ->method('findById')
            ->with($occurrence->id)
            ->willReturn($occurrence);

        $dispatchRepo->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Dispatch::class));

        $auditLogRepo->expects($this->once())
            ->method('log')
            ->with(
                'Dispatch',
                $this->isType('string'),
                'created',
                'Sistema',
                null,
                $this->isType('array'),
                ['occurrence_id' => $occurrence->id]
            );

        $useCase = new CreateDispatchUseCase($dispatchRepo, $occurrenceRepo, $auditLogRepo);
        $result = $useCase->execute($occurrence->id, 'ABT-01');

        $this->assertInstanceOf(Dispatch::class, $result);
        $this->assertEquals('ABT-01', $result->resourceCode);
        $this->assertEquals($occurrence->id, $result->occurrenceId);
    }

    public function test_throws_exception_if_occurrence_not_found()
    {
        $dispatchRepo = $this->createMock(DispatchRepositoryInterface::class);
        $occurrenceRepo = $this->createMock(OccurrenceRepositoryInterface::class);
        $auditLogRepo = $this->createMock(AuditLogRepositoryInterface::class);

        $occurrenceRepo->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $useCase = new CreateDispatchUseCase($dispatchRepo, $occurrenceRepo, $auditLogRepo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Occurrence not found');

        $useCase->execute('invalid-id', 'ABT-01');
    }
}
