<?php

namespace Application\Providers;

use Application\Factories\OccurrenceFactory;
use Application\Scenarios\ConcurrencyScenario;
use Application\Scenarios\HappyPathScenario;
use Application\Scenarios\IdempotencyScenario;
use Application\Scenarios\UpdateScenario;
use Application\Services\ScenarioExecutor;
use Domain\Interfaces\CoreApiClientInterface;
use Domain\Interfaces\LoggerInterface;
use Infrastructure\Adapters\HttpCoreApiClient;
use Infrastructure\Adapters\LaravelLogger;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoggerInterface::class, LaravelLogger::class);
        $this->app->singleton(CoreApiClientInterface::class, HttpCoreApiClient::class);
        $this->app->singleton(OccurrenceFactory::class);
        $this->app->bind(HappyPathScenario::class, function ($app) {
            return new HappyPathScenario(
                $app->make(OccurrenceFactory::class),
                $app->make(CoreApiClientInterface::class),
                $app->make(LoggerInterface::class)
            );
        });

        $this->app->bind(IdempotencyScenario::class, function ($app) {
            return new IdempotencyScenario(
                $app->make(OccurrenceFactory::class),
                $app->make(CoreApiClientInterface::class),
                $app->make(LoggerInterface::class)
            );
        });

        $this->app->bind(ConcurrencyScenario::class, function ($app) {
            return new ConcurrencyScenario(
                $app->make(OccurrenceFactory::class),
                $app->make(LoggerInterface::class)
            );
        });

        $this->app->bind(UpdateScenario::class, function ($app) {
            return new UpdateScenario(
                $app->make(OccurrenceFactory::class),
                $app->make(CoreApiClientInterface::class),
                $app->make(LoggerInterface::class)
            );
        });

        $this->app->singleton(ScenarioExecutor::class, function ($app) {
            $executor = new ScenarioExecutor(
                $app->make(LoggerInterface::class)
            );

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
