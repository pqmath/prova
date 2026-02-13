<?php

namespace tests\Unit\Infrastructure\Adapters;

use Domain\Entities\Occurrence;
use Domain\ValueObjects\OccurrenceType;
use Illuminate\Support\Facades\Http;
use Infrastructure\Adapters\HttpCoreApiClient;
use Psr\Log\LoggerInterface;
use tests\TestCase;

class HttpCoreApiClientTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        // BaseUrl, ApiKey, Timeout
        $this->client = new HttpCoreApiClient('http://test-api.com', 'test-key', 3);
    }

    public function test_check_health_returns_true_on_200()
    {
        Http::fake([
            '*/health' => Http::response([], 200),
        ]);

        $this->assertTrue($this->client->checkHealth());
    }

    public function test_check_health_returns_false_on_500()
    {
        Http::fake([
            '*/health' => Http::response([], 500),
        ]);

        $this->assertFalse($this->client->checkHealth());
    }

    public function test_check_health_returns_false_on_exception()
    {
        Http::fake(function ($request) {
            throw new \Exception('Network error');
        });

        $this->assertFalse($this->client->checkHealth());
    }

    public function test_send_occurrence_returns_success_response()
    {
        Http::fake([
            '*/occurrences' => Http::response(['id' => '123'], 201),
        ]);

        $occurrence = new \Domain\Entities\Occurrence(
            'EXT-123',
            \Domain\ValueObjects\OccurrenceType::IncendioUrbano,
            'Test description',
            new \DateTimeImmutable()
        );

        $key = new \Domain\ValueObjects\IdempotencyKey('key-123');

        $response = $this->client->sendOccurrence($occurrence, $key);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(['id' => '123'], $response->getBody());
    }

    public function test_send_occurrence_returns_error_on_exception()
    {
        Http::fake(function ($request) {
            throw new \Exception('Connection refused');
        });

        $occurrence = new \Domain\Entities\Occurrence(
            'EXT-123',
            \Domain\ValueObjects\OccurrenceType::IncendioUrbano,
            'Test description',
            new \DateTimeImmutable()
        );

        $key = new \Domain\ValueObjects\IdempotencyKey('key-123');

        $response = $this->client->sendOccurrence($occurrence, $key);

        $this->assertEquals(503, $response->getStatusCode());
        $this->assertEquals('Service Unavailable', $response->getBody()['error']);
    }
}
