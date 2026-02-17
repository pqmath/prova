<?php

namespace Tests\Feature;

use Application\UseCases\RequestCommandUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;
use Illuminate\Support\Str;

class ControllerExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_integration_controller_handles_exceptions()
    {
        $mock = Mockery::mock(RequestCommandUseCase::class);
        $mock->shouldReceive('execute')->andThrow(new \Exception("Simulated Error"));
        $this->app->instance(RequestCommandUseCase::class, $mock);

        $payload = [
            'external_id' => 'EXT-FAIL',
            'type' => 'incendio_urbano',
            'description' => 'Test Error',
            'reported_at' => '2026-02-01 10:00:00',
        ];

        $this->withHeaders([
            'X-API-Key' => 'bombeiros-api-key-2026',
            'Idempotency-Key' => Str::uuid()->toString()
        ])->postJson('/api/integrations/occurrences', $payload)
            ->assertStatus(400)
            ->assertJson(['error' => 'Simulated Error']);
    }

    public function test_occurrence_controller_start_handles_exceptions()
    {
        $mock = Mockery::mock(RequestCommandUseCase::class);
        $mock->shouldReceive('execute')->andThrow(new \Exception("Start Error"));
        $this->app->instance(RequestCommandUseCase::class, $mock);

        $uuid = Str::uuid()->toString();

        $this->withHeaders([
            'X-API-Key' => 'bombeiros-api-key-2026',
            'Idempotency-Key' => Str::uuid()->toString()
        ])->postJson("/api/occurrences/{$uuid}/start")
            ->assertStatus(400)
            ->assertJson(['error' => 'Start Error']);
    }

    public function test_occurrence_controller_resolve_handles_exceptions()
    {
        $mock = Mockery::mock(RequestCommandUseCase::class);
        $mock->shouldReceive('execute')->andThrow(new \Exception("Resolve Error"));
        $this->app->instance(RequestCommandUseCase::class, $mock);

        $uuid = Str::uuid()->toString();

        $this->withHeaders([
            'X-API-Key' => 'bombeiros-api-key-2026',
            'Idempotency-Key' => Str::uuid()->toString()
        ])->postJson("/api/occurrences/{$uuid}/resolve")
            ->assertStatus(400)
            ->assertJson(['error' => 'Resolve Error']);
    }

    public function test_dispatch_controller_handles_exceptions()
    {
        $mock = Mockery::mock(RequestCommandUseCase::class);
        $mock->shouldReceive('execute')->andThrow(new \Exception("Dispatch Error"));
        $this->app->instance(RequestCommandUseCase::class, $mock);

        $uuid = Str::uuid()->toString();

        $this->withHeaders([
            'X-API-Key' => 'bombeiros-api-key-2026',
            'Idempotency-Key' => Str::uuid()->toString()
        ])->postJson("/api/occurrences/{$uuid}/dispatches", ['resourceCode' => 'ABT-01'])
            ->assertStatus(400)
            ->assertJson(['error' => 'Dispatch Error']);
    }
}
