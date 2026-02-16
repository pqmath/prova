<?php

namespace Tests\Unit\Application\UseCases;

use Application\Models\AuditLog;
use Application\UseCases\CreateDispatchUseCase;
use Domain\Entities\Dispatch;
use Domain\Entities\Occurrence;
use Domain\Enums\DispatchStatus;
use Domain\Enums\OccurrenceType;
use Domain\Factories\OccurrenceFactory;
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
        $factory = new OccurrenceFactory();

        $occurrence = $factory->create('EXT-123', 'incendio_urbano', 'Desc', '2026-01-01');

        $occurrenceRepo->expects($this->once())
            ->method('findById')
            ->with($occurrence->id)
            ->willReturn($occurrence);

        $dispatchRepo->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Dispatch::class));

        $useCase = new CreateDispatchUseCase($dispatchRepo, $occurrenceRepo);
        $result = $useCase->execute($occurrence->id, 'ABT-01');

        $this->assertInstanceOf(Dispatch::class, $result);
        $this->assertEquals('ABT-01', $result->resourceCode);
        $this->assertEquals($occurrence->id, $result->occurrenceId);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'Dispatch',
            'action' => 'created',
        ]);
    }

    public function test_throws_exception_if_occurrence_not_found()
    {
        $dispatchRepo = $this->createMock(DispatchRepositoryInterface::class);
        $occurrenceRepo = $this->createMock(OccurrenceRepositoryInterface::class);

        $occurrenceRepo->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $useCase = new CreateDispatchUseCase($dispatchRepo, $occurrenceRepo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Occurrence not found');

        $useCase->execute('invalid-id', 'ABT-01');
    }
}
