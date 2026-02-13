<?php

namespace tests\Unit\Application\Http\Controllers;

use Application\Services\ScenarioExecutor;
use Domain\ValueObjects\ScenarioResult;
use tests\TestCase;

class ScenarioControllerTest extends TestCase
{
    private $executorMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->executorMock = $this->createMock(ScenarioExecutor::class);
        $this->app->instance(ScenarioExecutor::class, $this->executorMock);
    }

    public function test_index_returns_list_of_scenarios()
    {
        $this->executorMock->method('listScenarios')->willReturn(['s1' => 'Description']);
        $this->executorMock->method('count')->willReturn(1);

        $response = $this->getJson('/api/scenarios');

        $response->assertStatus(200)
            ->assertJson([
                'scenarios' => ['s1' => 'Description'],
                'total' => 1,
            ]);
    }

    public function test_execute_all_returns_results()
    {
        $result = ScenarioResult::success('OK');
        $this->executorMock->method('executeAll')->willReturn(['s1' => $result->toArray()]);

        $response = $this->postJson('/api/scenarios/execute-all');

        $response->assertStatus(200)
            ->assertJsonPath('results.s1.success', true);
    }

    public function test_happy_path_success()
    {
        $result = ScenarioResult::success('Success');
        $this->executorMock->method('executeByName')->with('happy-path')->willReturn($result);

        $response = $this->postJson('/api/scenarios/happy-path');

        $response->assertStatus(200)
            ->assertJsonPath('result.success', true);
    }

    public function test_happy_path_failure_returns_500()
    {
        $result = ScenarioResult::failure('Error', 500);
        $this->executorMock->method('executeByName')->with('happy-path')->willReturn($result);

        $response = $this->postJson('/api/scenarios/happy-path');

        $response->assertStatus(500)
            ->assertJsonPath('result.success', false);
    }

    public function test_execute_all_handles_partial_failures()
    {
        $success = ScenarioResult::success('OK');
        $failure = ScenarioResult::failure('Error', 500);

        $this->executorMock->method('executeAll')->willReturn([
            's1' => $success->toArray(),
            's2' => $failure->toArray()
        ]);

        $response = $this->postJson('/api/scenarios/execute-all');

        $response->assertStatus(200)
            ->assertJsonPath('results.s1.success', true)
            ->assertJsonPath('results.s2.success', false);
    }

    public function test_idempotency_success()
    {
        $result = ScenarioResult::success('OK');
        $this->executorMock->method('executeByName')->with('idempotency')->willReturn($result);

        $response = $this->postJson('/api/scenarios/idempotency');

        $response->assertStatus(200)
            ->assertJsonPath('result.success', true)
            ->assertJsonPath('scenario', 'idempotency');
    }

    public function test_idempotency_failure()
    {
        $result = ScenarioResult::failure('Error', 500);
        $this->executorMock->method('executeByName')->with('idempotency')->willReturn($result);

        $response = $this->postJson('/api/scenarios/idempotency');

        $response->assertStatus(500)
            ->assertJsonPath('result.success', false);
    }

    public function test_concurrency_success()
    {
        $result = ScenarioResult::success('OK');
        $this->executorMock->method('executeByName')->with('concurrency')->willReturn($result);

        $response = $this->postJson('/api/scenarios/concurrency');

        $response->assertStatus(200)
            ->assertJsonPath('result.success', true)
            ->assertJsonPath('scenario', 'concurrency');
    }

    public function test_concurrency_failure()
    {
        $result = ScenarioResult::failure('Error', 500);
        $this->executorMock->method('executeByName')->with('concurrency')->willReturn($result);

        $response = $this->postJson('/api/scenarios/concurrency');

        $response->assertStatus(500)
            ->assertJsonPath('result.success', false);
    }

    public function test_update_success()
    {
        $result = ScenarioResult::success('OK');
        $this->executorMock->method('executeByName')->with('update')->willReturn($result);

        $response = $this->postJson('/api/scenarios/update');

        $response->assertStatus(200)
            ->assertJsonPath('result.success', true)
            ->assertJsonPath('scenario', 'update');
    }

    public function test_update_failure()
    {
        $result = ScenarioResult::failure('Error', 500);
        $this->executorMock->method('executeByName')->with('update')->willReturn($result);

        $response = $this->postJson('/api/scenarios/update');

        $response->assertStatus(500)
            ->assertJsonPath('result.success', false);
    }
}
