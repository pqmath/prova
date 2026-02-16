<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoints_reject_missing_api_key()
    {
        $this->postJson('/api/integrations/occurrences', [])
            ->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized. Invalid API Key.']);

        $this->getJson('/api/occurrences')
            ->assertStatus(401);

        $this->postJson('/api/occurrences/any-id/start')
            ->assertStatus(401);

        $this->postJson('/api/occurrences/any-id/resolve')
            ->assertStatus(401);

        $this->postJson('/api/occurrences/any-id/dispatches')
            ->assertStatus(401);
    }

    public function test_endpoints_reject_invalid_api_key()
    {
        $headers = ['X-API-Key' => 'invalid-key'];

        $this->withHeaders($headers)->getJson('/api/occurrences')
            ->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized. Invalid API Key.']);
    }
}
