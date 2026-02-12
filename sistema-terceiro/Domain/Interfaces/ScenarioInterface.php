<?php

namespace Domain\Interfaces;

use Domain\ValueObjects\ScenarioResult;

interface ScenarioInterface
{
    public function execute(): ScenarioResult;
    public function getName(): string;
    public function getDescription(): string;
}
