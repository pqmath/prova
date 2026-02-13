<?php

namespace tests\Unit\Domain\ValueObjects;

use Domain\ValueObjects\ScenarioResult;
use PHPUnit\Framework\TestCase;

class ScenarioResultTest extends TestCase
{
    public function test_success_factory()
    {
        $result = ScenarioResult::success('Operação OK', 200, ['data' => 1]);

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
        $this->assertEquals('Operação OK', $result->getMessage());
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals(['data' => 1], $result->getResponses());
    }

    public function test_failure_factory()
    {
        $result = ScenarioResult::failure('Erro fatal', 500);

        $this->assertTrue($result->isFailure());
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Erro fatal', $result->getMessage());
        $this->assertEquals(500, $result->getStatusCode());
    }

    public function test_to_array()
    {
        $result = ScenarioResult::success('OK');
        $array = $result->toArray();

        $this->assertTrue($array['success']);
        $this->assertEquals('OK', $array['message']);
        $this->assertEquals(202, $array['status_code']);
        $this->assertArrayHasKey('sent_at', $array);
    }

    public function test_get_sent_at()
    {
        $result = ScenarioResult::success('OK');
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getSentAt());
    }
}
