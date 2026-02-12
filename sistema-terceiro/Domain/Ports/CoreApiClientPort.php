<?php

namespace Domain\Ports;

use Domain\Entities\Occurrence;
use Domain\ValueObjects\IdempotencyKey;
use Infrastructure\DTOs\ApiResponse;

interface CoreApiClientPort
{
    public function sendOccurrence(Occurrence $occurrence, IdempotencyKey $key): ApiResponse;
    public function checkHealth(): bool;
}
