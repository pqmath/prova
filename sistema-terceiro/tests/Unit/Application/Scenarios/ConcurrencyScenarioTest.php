<?php

namespace tests\Unit\Application\Scenarios;

use Application\Factories\OccurrenceFactory;
use Application\Scenarios\ConcurrencyScenario;
use Domain\DTOs\ApiResponse;
use Domain\Entities\Occurrence;
use Domain\Interfaces\CoreApiClientInterface;
use Domain\Interfaces\LoggerInterface;
use Domain\ValueObjects\OccurrenceType;
use PHPUnit\Framework\TestCase;

class ConcurrencyScenarioTest extends TestCase
{
    public function test_execute_successfully_sends_multiple_requests()
    {
        $factory = $this->createMock(OccurrenceFactory::class);
        $apiClient = $this->createMock(CoreApiClientInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $occurrence = new Occurrence(
            'EXT-123',
            OccurrenceType::IncendioUrbano,
            'Desc',
            new \DateTimeImmutable()
        );
        $factory->method('createRandom')->willReturn($occurrence);

        // Expects sendOccurrence to be called 10 times
        $apiClient->expects($this->exactly(10))
            ->method('sendOccurrence')
            ->willReturn(new ApiResponse(202, ['status' => 'queued']));

        $scenario = new ConcurrencyScenario($factory, $apiClient, $logger);
        $result = $scenario->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('Todas as 10 ocorrências foram enviadas com sucesso', $result->getMessage());
    }
    public function test_has_name_and_description()
    {
        $factory = $this->createMock(OccurrenceFactory::class);
        $apiClient = $this->createMock(CoreApiClientInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $scenario = new ConcurrencyScenario($factory, $apiClient, $logger);

        $this->assertEquals('concurrency', $scenario->getName());
        $this->assertNotEmpty($scenario->getDescription());
    }
    public function test_execute_handles_partial_failure()
    {
        $factory = $this->createMock(OccurrenceFactory::class);
        $apiClient = $this->createMock(CoreApiClientInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $occurrence = new Occurrence(
            'EXT-123',
            OccurrenceType::IncendioUrbano,
            'Desc',
            new \DateTimeImmutable()
        );
        $factory->method('createRandom')->willReturn($occurrence);

        // 5 successes, 5 failures
        $apiClient->expects($this->exactly(10))
            ->method('sendOccurrence')
            ->willReturnOnConsecutiveCalls(
                new ApiResponse(202, ['status' => 'queued']),
                new ApiResponse(202, ['status' => 'queued']),
                new ApiResponse(202, ['status' => 'queued']),
                new ApiResponse(202, ['status' => 'queued']),
                new ApiResponse(202, ['status' => 'queued']),
                new ApiResponse(500, ['error' => 'fail']),
                new ApiResponse(500, ['error' => 'fail']),
                new ApiResponse(500, ['error' => 'fail']),
                new ApiResponse(500, ['error' => 'fail']),
                new ApiResponse(500, ['error' => 'fail'])
            );

        $scenario = new ConcurrencyScenario($factory, $apiClient, $logger);
        $result = $scenario->execute();

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Apenas 5 de 10 foram enviadas com sucesso', $result->getMessage());
    }

    public function test_execute_handles_exception()
    {
        $factory = $this->createMock(OccurrenceFactory::class);
        $apiClient = $this->createMock(CoreApiClientInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $factory->method('createRandom')->willThrowException(new \Exception('Critical Error'));

        $scenario = new ConcurrencyScenario($factory, $apiClient, $logger);
        $result = $scenario->execute();

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Erro ao executar cenário: Critical Error', $result->getMessage());
    }
}
