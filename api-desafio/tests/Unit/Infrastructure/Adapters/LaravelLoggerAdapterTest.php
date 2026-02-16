<?php

namespace Tests\Unit\Infrastructure\Adapters;

use Illuminate\Support\Facades\Log;
use Infrastructure\Adapters\LaravelLoggerAdapter;
use Tests\TestCase;

class LaravelLoggerAdapterTest extends TestCase
{
    public function test_logs_info_to_laravel_log_facade()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Test message', ['key' => 'value']);

        $adapter = new LaravelLoggerAdapter();
        $adapter->info('Test message', ['key' => 'value']);
    }

    public function test_logs_error_to_laravel_log_facade()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Error message', ['context' => 'test']);

        $adapter = new LaravelLoggerAdapter();
        $adapter->error('Error message', ['context' => 'test']);
    }

    public function test_logs_warning_to_laravel_log_facade()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Warning message', []);

        $adapter = new LaravelLoggerAdapter();
        $adapter->warning('Warning message');
    }

    public function test_logs_debug_to_laravel_log_facade()
    {
        Log::shouldReceive('debug')
            ->once()
            ->with('Debug message', []);

        $adapter = new LaravelLoggerAdapter();
        $adapter->debug('Debug message');
    }
}
