<?php

namespace Tests\Feature;

use Application\Models\AuditLog;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AuditLogControllerTest extends TestCase
{
    use DatabaseMigrations;

    private string $apiKey = 'bombeiros-api-key-2026';

    public function test_index_returns_paginated_audit_logs()
    {
        AuditLog::create([
            'entity_type' => 'Occurrence',
            'entity_id' => '123',
            'action' => 'created',
            'source' => 'Sistema',
            'after' => ['status' => 'reported']
        ]);

        $response = $this->getJson('/api/audit-logs', [
            'X-API-Key' => $this->apiKey
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'first_page_url',
                'last_page',
                'total'
            ])
            ->assertJsonCount(1, 'data');
    }

    public function test_index_respects_per_page_parameter()
    {
        AuditLog::factory()->count(5)->create();

        $response = $this->getJson('/api/audit-logs?per_page=2', [
            'X-API-Key' => $this->apiKey
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_index_requires_api_key()
    {
        $response = $this->getJson('/api/audit-logs');
        $response->assertStatus(401);
    }
}
