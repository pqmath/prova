<?php

namespace Application\Providers;

use Application\Factories\OccurrenceFactory;
use Application\Scenarios\ConcurrencyScenario;
use Application\Scenarios\HappyPathScenario;
use Application\Scenarios\IdempotencyScenario;
use Application\Scenarios\UpdateScenario;
use Application\Services\ScenarioExecutor;
use Domain\Ports\CoreApiClientPort;
use Domain\Ports\LoggerPort;
use Infrastructure\Adapters\HttpCoreApiClient;
use Infrastructure\Adapters\LaravelLogger;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ============================================================
        // INFRASTRUCTURE - Ports → Adapters
        // ============================================================

        $this->app->singleton(LoggerPort::class, LaravelLogger::class);

        $this->app->singleton(CoreApiClientPort::class, HttpCoreApiClient::class);

        // ============================================================
        // APPLICATION - Factories
        // ============================================================

        $this->app->singleton(OccurrenceFactory::class);

        // ============================================================
        // APPLICATION - Scenarios
        // ============================================================

        $this->app->bind(HappyPathScenario::class, function ($app) {
            return new HappyPathScenario(
                $app->make(OccurrenceFactory::class),
                $app->make(CoreApiClientPort::class),
                $app->make(LoggerPort::class)
            );
        });

        $this->app->bind(IdempotencyScenario::class, function ($app) {
            return new IdempotencyScenario(
                $app->make(OccurrenceFactory::class),
                $app->make(CoreApiClientPort::class),
                $app->make(LoggerPort::class)
            );
        });

        $this->app->bind(ConcurrencyScenario::class, function ($app) {
            return new ConcurrencyScenario(
                $app->make(OccurrenceFactory::class),
                $app->make(CoreApiClientPort::class),
                $app->make(LoggerPort::class)
            );
        });

        $this->app->bind(UpdateScenario::class, function ($app) {
            return new UpdateScenario(
                $app->make(OccurrenceFactory::class),
                $app->make(CoreApiClientPort::class),
                $app->make(LoggerPort::class)
            );
        });

        // ============================================================
        // APPLICATION - Services (ScenarioExecutor)
        // ============================================================

        $this->app->singleton(ScenarioExecutor::class, function ($app) {
            $executor = new ScenarioExecutor(
                $app->make(LoggerPort::class)
            );

            // Registrar todos os cenários
            $executor->registerScenario($app->make(HappyPathScenario::class));
            $executor->registerScenario($app->make(IdempotencyScenario::class));
            $executor->registerScenario($app->make(ConcurrencyScenario::class));
            $executor->registerScenario($app->make(UpdateScenario::class));

            return $executor;
        });
    }

    public function boot(): void
    {
        //
    }
}
