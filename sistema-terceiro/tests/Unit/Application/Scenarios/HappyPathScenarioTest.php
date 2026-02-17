<?php

namespace tests\Unit\Application\Scenarios;

use Application\Factories\OccurrenceFactory;
use Application\Scenarios\HappyPathScenario;
use Domain\DTOs\ApiResponse;
use Domain\Entities\Occurrence;
use Domain\Interfaces\CoreApiClientInterface;
use Domain\Interfaces\LoggerInterface;
use Domain\ValueObjects\OccurrenceType;
use PHPUnit\Framework\TestCase;

class HappyPathScenarioTest extends TestCase
{
    public function test_execute_successfully()
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

        $response = new ApiResponse(202, ['status' => 'queued']);
        $apiClient->method('sendOccurrence')->willReturn($response);

        $logger->expects($this->atLeastOnce())->method('info');

        $scenario = new HappyPathScenario($factory, $apiClient, $logger);
        $result = $scenario->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(202, $result->getStatusCode());
        $this->assertEquals('Ocorrência enviada com sucesso (caminho feliz)', $result->getMessage());
    }

    public function test_execute_handles_api_failure()
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

        $response = new ApiResponse(500, ['error' => 'Server Error']);
        $apiClient->method('sendOccurrence')->willReturn($response);

        $scenario = new HappyPathScenario($factory, $apiClient, $logger);
        $result = $scenario->execute();

        $this->assertTrue($result->isFailure());
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertStringContainsString('Resposta inesperada da API Core', $result->getMessage());
    }
    public function test_has_name_and_description()
    {
        $factory = $this->createMock(OccurrenceFactory::class);
        $apiClient = $this->createMock(CoreApiClientInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $scenario = new HappyPathScenario($factory, $apiClient, $logger);

        $this->assertEquals('happy-path', $scenario->getName());
        $this->assertNotEmpty($scenario->getDescription());
    }
    public function test_execute_handles_exception()
    {
        $factory = $this->createMock(OccurrenceFactory::class);
        $apiClient = $this->createMock(CoreApiClientInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $factory->method('createRandom')->willThrowException(new \RuntimeException('Unexpected Error'));

        $scenario = new HappyPathScenario($factory, $apiClient, $logger);
        $result = $scenario->execute();

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Erro ao executar cenário: Unexpected Error', $result->getMessage());
    }
}
