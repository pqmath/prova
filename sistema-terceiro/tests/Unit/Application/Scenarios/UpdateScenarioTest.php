<?php

namespace tests\Unit\Application\Scenarios;

use Application\Factories\OccurrenceFactory;
use Application\Scenarios\UpdateScenario;
use Domain\DTOs\ApiResponse;
use Domain\Entities\Occurrence;
use Domain\Interfaces\CoreApiClientInterface;
use Domain\Interfaces\LoggerInterface;
use Domain\ValueObjects\OccurrenceType;
use PHPUnit\Framework\TestCase;

class UpdateScenarioTest extends TestCase
{
    public function test_execute_successfully_sends_update_request()
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

        $apiClient->expects($this->exactly(2))
            ->method('sendOccurrence')
            ->willReturn(new ApiResponse(202, ['status' => 'queued']));

        $scenario = new UpdateScenario($factory, $apiClient, $logger);
        $result = $scenario->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Ocorrência criada e atualizada com sucesso', $result->getMessage());
    }
    public function test_has_name_and_description()
    {
        $factory = $this->createMock(OccurrenceFactory::class);
        $apiClient = $this->createMock(CoreApiClientInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $scenario = new UpdateScenario($factory, $apiClient, $logger);

        $this->assertEquals('update', $scenario->getName());
        $this->assertNotEmpty($scenario->getDescription());
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

        $apiClient->method('sendOccurrence')->willReturnOnConsecutiveCalls(
            new ApiResponse(202, ['status' => 'created']),
            new ApiResponse(500, ['error' => 'fail'])
        );

        $scenario = new UpdateScenario($factory, $apiClient, $logger);
        $result = $scenario->execute();

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Respostas inesperadas no teste de atualização', $result->getMessage());
    }

    public function test_execute_handles_exception()
    {
        $factory = $this->createMock(OccurrenceFactory::class);
        $apiClient = $this->createMock(CoreApiClientInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $factory->method('createRandom')->willThrowException(new \Exception('Update Error'));

        $scenario = new UpdateScenario($factory, $apiClient, $logger);
        $result = $scenario->execute();

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Erro ao executar cenário: Update Error', $result->getMessage());
    }
}
