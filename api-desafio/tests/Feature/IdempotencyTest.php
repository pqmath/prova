<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_request_returns_same_response_and_does_not_duplicate()
    {
        $payload = [
            'externalId' => 'EXT-IDEM-001',
            'type' => 'incendio_urbano',
            'description' => 'Test',
            'reportedAt' => '2026-02-01T10:00:00'
        ];

        $headers = [
            'Idempotency-Key' => 'key-fixed-123',
            'X-API-Key' => 'bombeiros-api-key-2026'
        ];

        $response1 = $this->postJson('/api/integrations/occurrences', $payload, $headers);
        $response1->assertStatus(202);
        $commandId1 = $response1->json('commandId');

        $response2 = $this->postJson('/api/integrations/occurrences', $payload, $headers);
        $response2->assertStatus(202);
        $commandId2 = $response2->json('commandId');

        $this->assertEquals($commandId1, $commandId2);

        $this->assertDatabaseCount('event_inboxes', 1);
    }

    public function test_different_payload_same_key_returns_original_response_ignoring_payload()
    {

        $headers = [
            'Idempotency-Key' => 'key-fixed-456',
            'X-API-Key' => 'bombeiros-api-key-2026'
        ];

        $validPayloadA = [
            'externalId' => 'EXT-IDEM-A',
            'type' => 'incendio_urbano',
            'description' => 'A',
            'reportedAt' => '2026-02-01T10:00:00'
        ];
        $response1 = $this->postJson('/api/integrations/occurrences', $validPayloadA, $headers);
        $response1->assertStatus(202);
        $id1 = $response1->json('commandId');

        $validPayloadB = [
            'externalId' => 'EXT-IDEM-B',
            'type' => 'incendio_urbano',
            'description' => 'B',
            'reportedAt' => '2026-02-01T10:00:00'
        ];
        $response2 = $this->postJson('/api/integrations/occurrences', $validPayloadB, $headers);
        $response2->assertStatus(202);
        $id2 = $response2->json('commandId');

        $this->assertEquals($id1, $id2);
        $this->assertDatabaseCount('event_inboxes', 1);

        $this->assertDatabaseHas('event_inboxes', [
            'id' => $id1,
        ]);
    }
}
