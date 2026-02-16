<?php

namespace Tests\Unit\Application\UseCases;

use Application\DTOs\CreateOccurrenceDTO;
use Application\UseCases\CreateOccurrenceUseCase;
use Domain\Entities\Occurrence;
use Domain\Enums\OccurrenceStatus;
use Domain\Enums\OccurrenceType;
use Domain\Factories\OccurrenceFactory;
use Domain\Repositories\OccurrenceRepositoryInterface;
use Domain\Services\MessageBrokerInterface;
use PHPUnit\Framework\TestCase;

class CreateOccurrenceUseCaseTest extends TestCase
{
    public function test_can_create_new_occurrence()
    {
        $repository = $this->createMock(OccurrenceRepositoryInterface::class);
        $broker = $this->createMock(MessageBrokerInterface::class);
        $factory = new OccurrenceFactory();

        $repository->expects($this->once())
            ->method('findByExternalId')
            ->with('EXT-123')
            ->willReturn(null);

        $repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Occurrence::class));

        $broker->expects($this->once())
            ->method('publish')
            ->with('events', 'occurrence.created', $this->anything());

        $useCase = new CreateOccurrenceUseCase($factory, $repository, $broker);

        $dto = new CreateOccurrenceDTO(
            'EXT-123',
            'incendio_urbano',
            'Fire at street X',
            '2026-02-01 10:00:00'
        );

        $result = $useCase->execute($dto);

        $this->assertInstanceOf(Occurrence::class, $result['occurrence']);
        $this->assertEquals('created', $result['action']);
        $this->assertEquals('EXT-123', $result['occurrence']->externalId);
    }

    public function test_can_update_existing_occurrence()
    {
        $repository = $this->createMock(OccurrenceRepositoryInterface::class);
        $broker = $this->createMock(MessageBrokerInterface::class);
        $factory = new OccurrenceFactory();

        $existing = $factory->create('EXT-123', 'incendio_urbano', 'Old Desc', '2026-01-01 00:00:00');

        $repository->expects($this->once())
            ->method('findByExternalId')
            ->with('EXT-123')
            ->willReturn($existing);

        $repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Occurrence::class));

        $broker->expects($this->never())->method('publish');

        $useCase = new CreateOccurrenceUseCase($factory, $repository, $broker);

        $dto = new CreateOccurrenceDTO(
            'EXT-123',
            'incendio_urbano',
            'New Description',
            '2026-02-01 10:00:00'
        );

        $result = $useCase->execute($dto);

        $this->assertInstanceOf(Occurrence::class, $result['occurrence']);
        $this->assertEquals('updated', $result['action']);
        $this->assertEquals('New Description', $result['occurrence']->description);
        $this->assertNotNull($result['before']);
    }
}
