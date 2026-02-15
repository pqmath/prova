<?php

namespace Infrastructure\Adapters;

use Domain\Services\LoggerInterface;
use Illuminate\Support\Facades\Log;

class LaravelLoggerAdapter implements LoggerInterface
{
    public function info(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        Log::debug($message, $context);
    }
}
