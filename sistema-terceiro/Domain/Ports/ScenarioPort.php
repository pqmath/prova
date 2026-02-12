<?php

namespace Domain\Ports;

use Domain\ValueObjects\ScenarioResult;

interface ScenarioPort
{
    public function execute(): ScenarioResult;
    public function getName(): string;
    public function getDescription(): string;
}
