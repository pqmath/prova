<?php

namespace Tests\Unit\Infrastructure\Repositories;

use Domain\Enums\OccurrenceType;
use Domain\Factories\OccurrenceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Repositories\OccurrenceRepository;
use Tests\TestCase;

class OccurrenceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private OccurrenceRepository $repository;
    private OccurrenceFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new OccurrenceFactory();
        $this->repository = new OccurrenceRepository($this->factory);
    }

    public function test_can_save_and_retrieve_occurrence_by_id()
    {
        $occurrence = $this->factory->create('EXT-REPO-001', 'incendio_urbano', 'Test Description', '2026-01-01');

        $this->repository->save($occurrence);

        $retrieved = $this->repository->findById($occurrence->id);

        $this->assertNotNull($retrieved);
        $this->assertEquals($occurrence->id, $retrieved->id);
        $this->assertEquals($occurrence->externalId, $retrieved->externalId);
        $this->assertEquals(OccurrenceType::FIRE, $retrieved->type);
    }

    public function test_can_retrieve_occurrence_by_external_id()
    {
        $occurrence = $this->factory->create('EXT-REPO-002', 'incendio_urbano', 'Test Description', '2026-01-01');

        $this->repository->save($occurrence);

        $retrieved = $this->repository->findByExternalId('EXT-REPO-002');

        $this->assertNotNull($retrieved);
        $this->assertEquals($occurrence->id, $retrieved->id);
    }

    public function test_find_by_id_returns_null_when_not_found()
    {
        $retrieved = $this->repository->findById('00000000-0000-0000-0000-000000000000');
        $this->assertNull($retrieved);
    }

    public function test_find_by_external_id_returns_null_when_not_found()
    {
        $retrieved = $this->repository->findByExternalId('non-existent-ext-id');
        $this->assertNull($retrieved);
    }

    public function test_can_list_occurrences_with_filters()
    {
        $occ1 = $this->factory->create('FILTER-001', 'incendio_urbano', 'Fire A', '2026-01-01');
        $occ2 = $this->factory->create('FILTER-002', 'resgate_veicular', 'Rescue B', '2026-01-02');
        $occ3 = $this->factory->create('FILTER-003', 'incendio_urbano', 'Fire C', '2026-01-03');

        $this->repository->save($occ1);
        $this->repository->save($occ2);
        $this->repository->save($occ3);

        $results = $this->repository->list(['type' => 'incendio_urbano']);
        $this->assertCount(2, $results['data']);
        $this->assertEquals('FILTER-003', $results['data'][0]['external_id']);
        $this->assertEquals('FILTER-001', $results['data'][1]['external_id']);

        $resultsSearch = $this->repository->list(['search' => 'Rescue']);
        $this->assertCount(1, $resultsSearch['data']);
        $this->assertEquals('FILTER-002', $resultsSearch['data'][0]['external_id']);

        $resultsStatus = $this->repository->list(['status' => 'reported']);
        $this->assertCount(3, $resultsStatus['data']);
    }
}
