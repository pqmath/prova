<?php

namespace tests\Unit\Application\Http\Controllers;

use Domain\Interfaces\CoreApiClientInterface;
use tests\TestCase;

class HealthControllerTest extends TestCase
{
    private $coreApiClientMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coreApiClientMock = $this->createMock(CoreApiClientInterface::class);
        $this->app->instance(CoreApiClientInterface::class, $this->coreApiClientMock);
    }

    public function test_self_health_returns_healthy()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'service' => 'sistema-terceiro',
            ]);
    }

    public function test_core_health_returns_healthy_when_api_up()
    {
        $this->coreApiClientMock->method('checkHealth')->willReturn(true);

        $response = $this->getJson('/api/health/core');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'service' => 'api-core',
            ]);
    }

    public function test_core_health_returns_unhealthy_when_api_down()
    {
        $this->coreApiClientMock->method('checkHealth')->willReturn(false);

        $response = $this->getJson('/api/health/core');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'service' => 'api-core',
            ]);
    }
}
