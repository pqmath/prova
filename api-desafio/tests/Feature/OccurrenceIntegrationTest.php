<?php

namespace Tests\Feature;

use Application\UseCases\CreateDispatchUseCase;
use Application\UseCases\ResolveOccurrenceUseCase;
use Application\UseCases\StartOccurrenceUseCase;
use Domain\Entities\Occurrence;
use Domain\Enums\OccurrenceStatus;
use Domain\Enums\OccurrenceType;
use Domain\Factories\OccurrenceFactory;
use Domain\Repositories\OccurrenceRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OccurrenceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_can_receive_external_occurrence()
    {
        $payload = [
            'externalId' => 'EXT-TEST-001',
            'type' => 'incendio_urbano',
            'description' => 'Fire alarm at downtown',
            'reportedAt' => '2026-02-01T15:00:00-03:00'
        ];

        $headers = [
            'Idempotency-Key' => 'key-' . uniqid(),
            'X-API-Key' => 'bombeiros-api-key-2026'
        ];

        $response = $this->postJson('/api/integrations/occurrences', $payload, $headers);

        $response->assertStatus(202);
        $response->assertJsonStructure(['commandId', 'status']);

        $this->assertDatabaseHas('event_inboxes', [
            'type' => 'occurrence.received',
            'source' => 'system_integration'
        ]);
    }

    public function test_can_list_occurrences()
    {
        $factory = new OccurrenceFactory();
        $repo = app(OccurrenceRepositoryInterface::class);
        $occ = $factory->create('EXT-LIST-001', 'incendio_urbano', 'Desc', '2026-01-01');
        $repo->save($occ);

        $headers = ['X-API-Key' => 'bombeiros-api-key-2026'];
        $response = $this->getJson('/api/occurrences?status=reported', $headers);

        $response->assertStatus(200);
        $response->assertJsonFragment(['external_id' => 'EXT-LIST-001']);
    }

    public function test_can_start_occurrence()
    {
        $factory = new OccurrenceFactory();
        $repo = app(OccurrenceRepositoryInterface::class);
        $occ = $factory->create('EXT-START-001', 'incendio_urbano', 'Desc', '2026-01-01');
        $repo->save($occ);

        $headers = [
            'Idempotency-Key' => 'start-' . $occ->id,
            'X-API-Key' => 'bombeiros-api-key-2026'
        ];
        $response = $this->postJson("/api/occurrences/{$occ->id}/start", [], $headers);

        $response->assertStatus(202);

        app(StartOccurrenceUseCase::class)->execute($occ->id);
        $this->assertDatabaseHas('occurrences', [
            'id' => $occ->id,
            'status' => OccurrenceStatus::IN_PROGRESS->value
        ]);
    }

    public function test_can_resolve_occurrence()
    {
        $factory = new OccurrenceFactory();
        $repo = app(OccurrenceRepositoryInterface::class);
        $occ = $factory->reconstitute(
            uuid_create(),
            'EXT-RESOLVE-001',
            'incendio_urbano',
            'in_progress',
            'Desc',
            '2026-01-01',
            '2026-01-01',
            '2026-01-01'
        );
        $repo->save($occ);

        $headers = [
            'Idempotency-Key' => 'resolve-' . $occ->id,
            'X-API-Key' => 'bombeiros-api-key-2026'
        ];
        $response = $this->postJson("/api/occurrences/{$occ->id}/resolve", [], $headers);

        $response->assertStatus(202);

        app(ResolveOccurrenceUseCase::class)->execute($occ->id);
        $this->assertDatabaseHas('occurrences', [
            'id' => $occ->id,
            'status' => OccurrenceStatus::RESOLVED->value
        ]);
    }

    public function test_can_create_dispatch()
    {
        $factory = new OccurrenceFactory();
        $repo = app(OccurrenceRepositoryInterface::class);
        $occ = $factory->create('EXT-DISPATCH-001', 'incendio_urbano', 'Desc', '2026-01-01');
        $repo->save($occ);

        $headers = [
            'Idempotency-Key' => 'dispatch-' . $occ->id,
            'X-API-Key' => 'bombeiros-api-key-2026'
        ];

        $response = $this->postJson("/api/occurrences/{$occ->id}/dispatches", [
            'resourceCode' => 'ABT-05'
        ], $headers);

        $response->assertStatus(202);

        app(CreateDispatchUseCase::class)->execute($occ->id, 'ABT-05');
        $this->assertDatabaseHas('dispatches', [
            'occurrence_id' => $occ->id,
            'resource_code' => 'ABT-05'
        ]);
    }
}
