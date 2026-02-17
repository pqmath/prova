<?php

namespace Application\Providers;

use Domain\Repositories\AuditLogRepositoryInterface;
use Domain\Repositories\DispatchRepositoryInterface;
use Domain\Repositories\EventInboxRepositoryInterface;
use Domain\Repositories\OccurrenceRepositoryInterface;
use Domain\Services\LoggerInterface;
use Domain\Services\MessageBrokerInterface;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Adapters\LaravelLoggerAdapter;
use Infrastructure\Console\Commands\ProcessOccurrencesCommand;
use Infrastructure\Console\Commands\PublishPendingEventsCommand;
use Infrastructure\Repositories\AuditLogRepository;
use Infrastructure\Repositories\DispatchRepository;
use Infrastructure\Repositories\EventInboxRepository;
use Infrastructure\Repositories\OccurrenceRepository;
use Infrastructure\Services\RabbitMQ\RabbitMQClient;
use Infrastructure\Services\RabbitMQ\RabbitMQPublisher;
use Infrastructure\Console\Commands\SimulateDispatchMovement;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            OccurrenceRepositoryInterface::class,
            OccurrenceRepository::class
        );

        $this->app->bind(
            EventInboxRepositoryInterface::class,
            EventInboxRepository::class
        );

        $this->app->bind(
            DispatchRepositoryInterface::class,
            DispatchRepository::class
        );

        $this->app->singleton(RabbitMQClient::class, function () {
            $config = config('rabbitmq');
            return new RabbitMQClient(
                $config['host'],
                $config['port'],
                $config['user'],
                $config['password'],
                $config['vhost']
            );
        });

        $this->app->bind(
            MessageBrokerInterface::class,
            RabbitMQPublisher::class
        );

        $this->app->bind(
            LoggerInterface::class,
            LaravelLoggerAdapter::class
        );

        $this->app->bind(
            AuditLogRepositoryInterface::class,
            AuditLogRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->commands([
            PublishPendingEventsCommand::class,
            ProcessOccurrencesCommand::class,
            SimulateDispatchMovement::class,
        ]);
    }
}
