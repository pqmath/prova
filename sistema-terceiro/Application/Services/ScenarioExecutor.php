<?php

namespace Application\Services;

use Domain\Interfaces\LoggerInterface;
use Domain\Interfaces\ScenarioInterface;
use Domain\ValueObjects\ScenarioResult;
use InvalidArgumentException;

final class ScenarioExecutor
{
    private array $scenarios = [];

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function registerScenario(ScenarioInterface $scenario): void
    {
        $this->scenarios[$scenario->getName()] = $scenario;

        $this->logger->debug('Cenário registrado', [
            'name' => $scenario->getName(),
            'description' => $scenario->getDescription(),
        ]);
    }

    public function executeByName(string $name): ScenarioResult
    {
        if (!isset($this->scenarios[$name])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cenário "%s" não encontrado. Cenários disponíveis: %s',
                    $name,
                    implode(', ', array_keys($this->scenarios))
                )
            );
        }

        $scenario = $this->scenarios[$name];

        $this->logger->info('Executando cenário', [
            'name' => $scenario->getName(),
            'description' => $scenario->getDescription(),
        ]);

        return $scenario->execute();
    }

    public function executeAll(): array
    {
        $results = [];

        foreach ($this->scenarios as $name => $scenario) {
            $this->logger->info("Executando cenário: {$name}");

            $result = $scenario->execute();
            $results[$name] = $result->toArray();
        }

        return $results;
    }

    public function listScenarios(): array
    {
        $list = [];

        foreach ($this->scenarios as $name => $scenario) {
            $list[] = [
                'name' => $scenario->getName(),
                'description' => $scenario->getDescription(),
            ];
        }

        return $list;
    }

    public function hasScenario(string $name): bool
    {
        return isset($this->scenarios[$name]);
    }

    public function count(): int
    {
        return count($this->scenarios);
    }
}
