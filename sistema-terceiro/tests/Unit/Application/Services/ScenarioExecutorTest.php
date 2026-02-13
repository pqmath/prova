<?php

namespace tests\Unit\Application\Services;

use Application\Services\ScenarioExecutor;
use Domain\Interfaces\LoggerInterface;
use Domain\Interfaces\ScenarioInterface;
use Domain\ValueObjects\ScenarioResult;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ScenarioExecutorTest extends TestCase
{
    private ScenarioExecutor $executor;
    private $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->executor = new ScenarioExecutor($this->loggerMock);
    }

    public function test_can_register_and_execute_scenario()
    {
        $scenarioMock = $this->createMock(ScenarioInterface::class);
        $scenarioMock->method('getName')->willReturn('test-scenario');
        $scenarioMock->method('execute')->willReturn(ScenarioResult::success('OK'));

        $this->executor->registerScenario($scenarioMock);
        $result = $this->executor->executeByName('test-scenario');

        $this->assertTrue($result->isSuccess());
    }

    public function test_throws_exception_if_scenario_not_found()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cenário "invalid-scenario" não encontrado');

        $this->executor->executeByName('invalid-scenario');
    }

    public function test_execute_all_runs_all_scenarios()
    {
        $scenario1 = $this->createMock(ScenarioInterface::class);
        $scenario1->method('getName')->willReturn('s1');
        $scenario1->method('execute')->willReturn(ScenarioResult::success('OK1'));

        $scenario2 = $this->createMock(ScenarioInterface::class);
        $scenario2->method('getName')->willReturn('s2');
        $scenario2->method('execute')->willReturn(ScenarioResult::success('OK2'));

        $this->executor->registerScenario($scenario1);
        $this->executor->registerScenario($scenario2);

        $results = $this->executor->executeAll();

        $this->assertCount(2, $results);
        $this->assertEquals('OK1', $results['s1']['message']);
        $this->assertEquals('OK2', $results['s2']['message']);
    }

    public function test_has_scenario_returns_true_for_existing_scenario()
    {
        $scenario = $this->createMock(ScenarioInterface::class);
        $scenario->method('getName')->willReturn('existing-scenario');
        $this->executor->registerScenario($scenario);

        $this->assertTrue($this->executor->hasScenario('existing-scenario'));
    }

    public function test_has_scenario_returns_false_for_non_existent_scenario()
    {
        $this->assertFalse($this->executor->hasScenario('non-existent'));
    }

    public function test_count_returns_number_of_scenarios()
    {
        $this->assertEquals(0, $this->executor->count());

        $scenario1 = $this->createMock(ScenarioInterface::class);
        $scenario1->method('getName')->willReturn('s1');
        $this->executor->registerScenario($scenario1);

        $this->assertEquals(1, $this->executor->count());

        $scenario2 = $this->createMock(ScenarioInterface::class);
        $scenario2->method('getName')->willReturn('s2');
        $this->executor->registerScenario($scenario2);

        $this->assertEquals(2, $this->executor->count());
    }

    public function test_list_scenarios()
    {
        $scenario = $this->createMock(ScenarioInterface::class);
        $scenario->method('getName')->willReturn('s1');
        $scenario->method('getDescription')->willReturn('Desc'); // Ensure this string matches assertion

        $this->executor->registerScenario($scenario);
        $list = $this->executor->listScenarios();

        $this->assertCount(1, $list);
        $this->assertEquals('Desc', $list[0]['description']);
    }
}
