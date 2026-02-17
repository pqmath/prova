<?php

namespace Tests\Feature;

use Domain\Factories\OccurrenceFactory;
use Domain\Repositories\OccurrenceRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class DispatchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_dispatch()
    {
        $factory = new OccurrenceFactory();
        $repo = app(OccurrenceRepositoryInterface::class);
        $occ = $factory->create('EXT-1', 'incendio_urbano', 'Desc 1', '2026-01-01');
        $repo->save($occ);

        $payload = ['resourceCode' => 'ABT-01'];

        $response = $this->withHeaders([
            'X-API-Key' => 'bombeiros-api-key-2026',
            'Idempotency-Key' => Str::uuid()->toString()
        ])->postJson("/api/occurrences/{$occ->id}/dispatches", $payload);

        $response->assertStatus(202)
            ->assertJson([
                'status' => 'accepted'
            ]);

        $this->assertDatabaseHas('event_inboxes', [
            'type' => 'dispatch.requested'
        ]);
    }

    public function test_validates_resource_code()
    {
        $factory = new OccurrenceFactory();
        $repo = app(OccurrenceRepositoryInterface::class);
        $occ = $factory->create('EXT-1', 'incendio_urbano', 'Desc 1', '2026-01-01');
        $repo->save($occ);

        $response = $this->withHeaders([
            'X-API-Key' => 'bombeiros-api-key-2026',
            'Idempotency-Key' => Str::uuid()->toString()
        ])->postJson("/api/occurrences/{$occ->id}/dispatches", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['resourceCode']);
    }

    public function test_returns_400_if_idempotency_key_missing()
    {
        $uuid = Str::uuid()->toString();
        $this->withHeaders(['X-API-Key' => 'bombeiros-api-key-2026'])
            ->postJson("/api/occurrences/{$uuid}/dispatches", ['resourceCode' => 'ABT-01'])
            ->assertStatus(400)
            ->assertJson(['error' => 'Idempotency-Key header is required']);
    }
}
