<?php

namespace Domain\Interfaces;

use Domain\DTOs\ApiResponse;
use Domain\Entities\Occurrence;
use Domain\ValueObjects\IdempotencyKey;

interface CoreApiClientInterface
{
    public function sendOccurrence(Occurrence $occurrence, IdempotencyKey $key): ApiResponse;
    public function checkHealth(): bool;
}
