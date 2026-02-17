<?php

namespace tests\Unit\Application\Providers;

use Application\Services\ScenarioExecutor;
use Domain\Interfaces\CoreApiClientInterface;
use Domain\Interfaces\LoggerInterface;
use Infrastructure\Adapters\HttpCoreApiClient;
use Infrastructure\Adapters\LaravelLogger;
use tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_binds_core_api_client_interface_to_http_implementation()
    {
        $this->assertInstanceOf(
            HttpCoreApiClient::class,
            $this->app->make(CoreApiClientInterface::class)
        );
    }

    public function test_binds_logger_interface_to_laravel_implementation()
    {
        $this->assertInstanceOf(
            LaravelLogger::class,
            $this->app->make(LoggerInterface::class)
        );
    }

    public function test_scenarios_are_registered_in_executor()
    {
        $this->mock(LoggerInterface::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });

        $this->mock(CoreApiClientInterface::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });

        $executor = $this->app->make(ScenarioExecutor::class);
        $scenarios = $executor->listScenarios();

        $scenarioNames = array_column($scenarios, 'name');

        $this->assertContains('happy-path', $scenarioNames);
        $this->assertContains('idempotency', $scenarioNames);
        $this->assertContains('concurrency', $scenarioNames);
        $this->assertContains('update', $scenarioNames);
    }
}
