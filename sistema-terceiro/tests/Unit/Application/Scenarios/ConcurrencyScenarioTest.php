<?php

namespace tests\Unit\Application\Scenarios;

use Application\Factories\OccurrenceFactory;
use Application\Scenarios\ConcurrencyScenario;
use DateTimeImmutable;
use Domain\Entities\Occurrence;
use Domain\Interfaces\LoggerInterface;
use Domain\ValueObjects\OccurrenceType;
use Illuminate\Support\Facades\Http;
use tests\TestCase;

class ConcurrencyScenarioTest extends TestCase
{
    public function test_execute_successfully_sends_multiple_requests()
    {
        Http::fake([
            '*' => Http::response(['status' => 'queued'], 202),
        ]);

        $factory = $this->createMock(OccurrenceFactory::class);
        $logger = $this->createMock(LoggerInterface::class);

        $occurrence = new Occurrence(
            'EXT-123',
            OccurrenceType::IncendioUrbano,
            'Desc',
            new DateTimeImmutable()
        );
        $factory->method('createRandom')->willReturn($occurrence);

        $scenario = new ConcurrencyScenario($factory, $logger);
        $result = $scenario->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('Todas as 10 ocorrências foram enviadas COM SUCESSO', $result->getMessage());
        $this->assertEquals(202, $result->getStatusCode());
    }

    public function test_has_name_and_description()
    {
        $factory = $this->createMock(OccurrenceFactory::class);
        $logger = $this->createMock(LoggerInterface::class);
        $scenario = new ConcurrencyScenario($factory, $logger);

        $this->assertEquals('concurrency', $scenario->getName());
        $this->assertStringContainsString('Concorrência REAL', $scenario->getDescription());
    }

    public function test_execute_handles_partial_failure()
    {
        Http::fake([
            '*' => Http::sequence()
                ->push(['status' => 'queued'], 202)
                ->push(['status' => 'queued'], 202)
                ->push(['status' => 'queued'], 202)
                ->push(['status' => 'queued'], 202)
                ->push(['status' => 'queued'], 202)
                ->push(['error' => 'fail'], 500)
                ->push(['error' => 'fail'], 500)
                ->push(['error' => 'fail'], 500)
                ->push(['error' => 'fail'], 500)
                ->push(['error' => 'fail'], 500)
        ]);

        $factory = $this->createMock(OccurrenceFactory::class);
        $logger = $this->createMock(LoggerInterface::class);

        $occurrence = new Occurrence(
            'EXT-123',
            OccurrenceType::IncendioUrbano,
            'Desc',
            new DateTimeImmutable()
        );
        $factory->method('createRandom')->willReturn($occurrence);

        $scenario = new ConcurrencyScenario($factory, $logger);
        $result = $scenario->execute();

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('5 de 10 foram enviadas com sucesso', $result->getMessage());
    }

    public function test_execute_handles_total_failure()
    {
        Http::fake([
            '*' => Http::response(['error' => 'fail'], 500),
        ]);

        $factory = $this->createMock(OccurrenceFactory::class);
        $logger = $this->createMock(LoggerInterface::class);

        $occurrence = new Occurrence(
            'EXT-123',
            OccurrenceType::IncendioUrbano,
            'Desc',
            new DateTimeImmutable()
        );
        $factory->method('createRandom')->willReturn($occurrence);

        $scenario = new ConcurrencyScenario($factory, $logger);
        $result = $scenario->execute();

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Todas as 10 requisições falharam', $result->getMessage());
    }

    public function test_execute_handles_exception()
    {
        $factory = $this->createMock(OccurrenceFactory::class);
        $logger = $this->createMock(LoggerInterface::class);

        $factory->method('createRandom')->willThrowException(new \Exception('Critical Error'));

        $scenario = new ConcurrencyScenario($factory, $logger);
        $result = $scenario->execute();

        $this->assertTrue($result->isFailure());
        $this->assertStringContainsString('Erro ao executar cenário: Critical Error', $result->getMessage());
    }
}
