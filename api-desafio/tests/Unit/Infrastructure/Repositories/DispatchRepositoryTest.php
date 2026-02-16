<?php

namespace Tests\Unit\Infrastructure\Repositories;

use Domain\Entities\Dispatch;
use Domain\Entities\Occurrence;
use Domain\Factories\OccurrenceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Repositories\DispatchRepository;
use Infrastructure\Repositories\OccurrenceRepository;
use Tests\TestCase;

class DispatchRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DispatchRepository $repository;
    private OccurrenceRepository $occurrenceRepository;
    private OccurrenceFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new OccurrenceFactory();
        $this->occurrenceRepository = new OccurrenceRepository($this->factory);
        $this->repository = new DispatchRepository();
    }

    public function test_can_save_and_retrieve_dispatches_by_occurrence_id()
    {
        $occurrence = $this->factory->create('EXT-DISP-001', 'incendio_urbano', 'Desc', '2026-01-01');
        $this->occurrenceRepository->save($occurrence);

        $dispatch = Dispatch::create($occurrence->id, 'ABT-01');
        $this->repository->save($dispatch);

        $results = $this->repository->findByOccurrenceId($occurrence->id);

        $this->assertCount(1, $results);
        $this->assertEquals($dispatch->id, $results[0]->id);
        $this->assertEquals('ABT-01', $results[0]->resourceCode);
    }

    public function test_find_by_occurrence_id_returns_empty_array_when_none_found()
    {
        $occurrence = $this->factory->create('EXT-DISP-002', 'incendio_urbano', 'Desc', '2026-01-01');
        $this->occurrenceRepository->save($occurrence);

        $results = $this->repository->findByOccurrenceId($occurrence->id);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }
}
