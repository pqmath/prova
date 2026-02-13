<?php

namespace tests\Unit\Infrastructure\Adapters;

use Illuminate\Support\Facades\Log;
use Infrastructure\Adapters\LaravelLogger;
use tests\TestCase;

class LaravelLoggerTest extends TestCase
{
    public function test_proxies_calls_to_laravel_facade()
    {
        Log::shouldReceive('info')->with('test info', ['key' => 'value'])->once();
        Log::shouldReceive('error')->with('test error', ['key' => 'value'])->once();
        Log::shouldReceive('warning')->with('test warning', ['key' => 'value'])->once();
        Log::shouldReceive('debug')->with('test debug', ['key' => 'value'])->once();

        $logger = new LaravelLogger();

        $logger->info('test info', ['key' => 'value']);
        $logger->error('test error', ['key' => 'value']);
        $logger->warning('test warning', ['key' => 'value']);
        $logger->debug('test debug', ['key' => 'value']);
    }
}
