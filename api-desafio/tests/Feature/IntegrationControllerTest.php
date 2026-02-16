<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class IntegrationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_occurrence_via_integration()
    {
        $payload = [
            'external_id' => 'EXT-123',
            'type' => 'incendio_urbano',
            'description' => 'Fire at street X',
            'reported_at' => '2026-02-01 10:00:00',
        ];

        $response = $this->withHeaders([
            'X-API-Key' => 'bombeiros-api-key-2026',
            'Idempotency-Key' => Str::uuid()->toString()
        ])->postJson('/api/integrations/occurrences', $payload);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'commandId',
                'status',
            ]);

        $this->assertDatabaseHas('event_inboxes', ['type' => 'occurrence.received']);
    }

    public function test_prevents_duplicates_via_idempotency_key_reuse()
    {
        $payload = [
            'external_id' => 'EXT-123',
            'type' => 'incendio_urbano',
            'description' => 'Fire at street X',
            'reported_at' => '2026-02-01 10:00:00',
        ];

        $key = Str::uuid()->toString();

        $this->withHeaders([
            'X-API-Key' => 'bombeiros-api-key-2026',
            'Idempotency-Key' => $key
        ])->postJson('/api/integrations/occurrences', $payload)->assertStatus(202);

        $this->withHeaders([
            'X-API-Key' => 'bombeiros-api-key-2026',
            'Idempotency-Key' => $key
        ])->postJson('/api/integrations/occurrences', [
                    'external_id' => 'EXT-123',
                    'type' => 'incendio_urbano',
                    'description' => 'Updated Description',
                    'reported_at' => '2026-02-01 10:00:00',
                ])->assertStatus(202);
    }

    public function test_validates_required_fields()
    {
        $response = $this->withHeaders([
            'X-API-Key' => 'bombeiros-api-key-2026',
            'Idempotency-Key' => Str::uuid()->toString()
        ])->postJson('/api/integrations/occurrences', []);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Missing required fields (externalId, type, description, reportedAt)'
            ]);
    }

    public function test_returns_400_if_idempotency_key_missing()
    {
        $payload = [
            'external_id' => 'EXT-123',
            'type' => 'incendio_urbano',
            'description' => 'Fire at street X',
            'reported_at' => '2026-02-01 10:00:00',
        ];

        $this->withHeaders(['X-API-Key' => 'bombeiros-api-key-2026'])
            ->postJson('/api/integrations/occurrences', $payload)
            ->assertStatus(400)
            ->assertJson(['error' => 'Idempotency-Key header is required']);
    }
}
