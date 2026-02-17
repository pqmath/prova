<?php

namespace Tests\Feature;

use Application\UseCases\RequestCommandUseCase;
use Domain\Factories\OccurrenceFactory;
use Domain\Repositories\OccurrenceRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class OccurrenceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_occurrences()
    {
        $factory = new OccurrenceFactory();
        $repo = app(OccurrenceRepositoryInterface::class);

        $occ1 = $factory->create('EXT-1', 'incendio_urbano', 'Desc 1', '2026-01-01');
        $occ2 = $factory->create('EXT-2', 'resgate_veicular', 'Desc 2', '2026-01-01');

        $repo->save($occ1);
        $repo->save($occ2);

        $response = $this->withHeaders(['X-API-Key' => 'bombeiros-api-key-2026'])
            ->getJson('/api/occurrences');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_occurrences_by_status()
    {
        $factory = new OccurrenceFactory();
        $repo = app(OccurrenceRepositoryInterface::class);

        $occ1 = $factory->create('EXT-1', 'incendio_urbano', 'Desc 1', '2026-01-01');
        $repo->save($occ1);

        $response = $this->withHeaders(['X-API-Key' => 'bombeiros-api-key-2026'])
            ->getJson('/api/occurrences?status=reported');
        $response->assertStatus(200);

        $data = $response->json();
        if (isset($data['data'])) {
            $this->assertCount(1, $data['data']);
        } else {
            $this->assertCount(1, $data);
        }

        $response = $this->withHeaders(['X-API-Key' => 'bombeiros-api-key-2026'])
            ->getJson('/api/occurrences?status=resolved');
        $response->assertStatus(200);
        $data = $response->json();
        if (isset($data['data'])) {
            $this->assertCount(0, $data['data']);
        } else {
            $this->assertCount(0, $data);
        }
    }

    public function test_can_start_occurrence()
    {
        $factory = new OccurrenceFactory();
        $repo = app(OccurrenceRepositoryInterface::class);
        $occ = $factory->create('EXT-1', 'incendio_urbano', 'Desc 1', '2026-01-01');
        $repo->save($occ);

        $response = $this->withHeaders([
            'X-API-Key' => 'bombeiros-api-key-2026',
            'Idempotency-Key' => Str::uuid()->toString()
        ])->postJson("/api/occurrences/{$occ->id}/start");

        $response->assertStatus(202)
            ->assertJson(['status' => 'accepted']);

        $this->assertDatabaseHas('event_inboxes', [
            'type' => 'occurrence.started'
        ]);
    }

    public function test_can_resolve_occurrence()
    {
        $factory = new OccurrenceFactory();
        $repo = app(OccurrenceRepositoryInterface::class);

        $uuid = Str::uuid()->toString();
        $occ = $factory->reconstitute(
            $uuid,
            'EXT-1',
            'incendio_urbano',
            'in_progress',
            'Desc',
            '2026-01-01',
            '2026-01-01',
            '2026-01-01'
        );
        $repo->save($occ);

        $response = $this->withHeaders([
            'X-API-Key' => 'bombeiros-api-key-2026',
            'Idempotency-Key' => Str::uuid()->toString()
        ])->postJson("/api/occurrences/{$occ->id}/resolve");

        $response->assertStatus(202)
            ->assertJson(['status' => 'accepted']);

        $this->assertDatabaseHas('event_inboxes', [
            'type' => 'occurrence.resolved'
        ]);
    }

    public function test_returns_400_if_idempotency_key_missing()
    {
        $uuid = Str::uuid()->toString();

        $this->withHeaders(['X-API-Key' => 'bombeiros-api-key-2026'])
            ->postJson("/api/occurrences/{$uuid}/start")
            ->assertStatus(400)
            ->assertJson(['error' => 'Idempotency-Key header is required']);

        $this->withHeaders(['X-API-Key' => 'bombeiros-api-key-2026'])
            ->postJson("/api/occurrences/{$uuid}/resolve")
            ->assertStatus(400)
            ->assertJson(['error' => 'Idempotency-Key header is required']);
    }

    public function test_can_retrieve_occurrence_details()
    {
        $factory = new OccurrenceFactory();
        $repo = app(OccurrenceRepositoryInterface::class);

        $occ = $factory->create('EXT-DETAIL-1', 'incendio_urbano', 'Detalhes', '2026-01-01');
        $repo->save($occ);

        $response = $this->withHeaders(['X-API-Key' => 'bombeiros-api-key-2026'])
            ->getJson("/api/occurrences/{$occ->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $occ->id,
                'externalId' => 'EXT-DETAIL-1'
            ]);
    }

    public function test_returns_404_if_occurrence_detail_not_found()
    {
        $this->withHeaders(['X-API-Key' => 'bombeiros-api-key-2026'])
            ->getJson("/api/occurrences/non-existent-id")
            ->assertStatus(404)
            ->assertJson(['error' => 'Occurrence not found']);
    }

    public function test_start_occurrence_handles_exceptions()
    {
        $this->mock(RequestCommandUseCase::class, function ($mock) {
            $mock->shouldReceive('execute')->andThrow(new \Exception('Simulated start error'));
        });

        $this->withHeaders([
            'X-API-Key' => 'bombeiros-api-key-2026',
            'Idempotency-Key' => Str::uuid()->toString()
        ])->postJson("/api/occurrences/any-id/start")
            ->assertStatus(400)
            ->assertJson(['error' => 'Simulated start error']);
    }

    public function test_resolve_occurrence_handles_exceptions()
    {
        $this->mock(RequestCommandUseCase::class, function ($mock) {
            $mock->shouldReceive('execute')->andThrow(new \Exception('Simulated resolve error'));
        });

        $this->withHeaders([
            'X-API-Key' => 'bombeiros-api-key-2026',
            'Idempotency-Key' => Str::uuid()->toString()
        ])->postJson("/api/occurrences/any-id/resolve")
            ->assertStatus(400)
            ->assertJson(['error' => 'Simulated resolve error']);
    }
}
