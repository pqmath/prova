<?php

namespace Infrastructure\Services\RabbitMQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;

class RabbitMQClient
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;
    private AMQPConnectionFactory $connectionFactory;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $vhost = '/',
        ?AMQPConnectionFactory $connectionFactory = null
    ) {
        $this->connectionFactory = $connectionFactory ?? new AMQPConnectionFactory();
    }

    public function getChannel(): AMQPChannel
    {
        if ($this->channel && $this->channel->is_open()) {
            return $this->channel;
        }

        if (!$this->connection || !$this->connection->isConnected()) {
            $this->connect();
        }

        $this->channel = $this->connection->channel();
        return $this->channel;
    }

    private function connect(): void
    {
        $this->connection = $this->connectionFactory->create(
            $this->host,
            $this->port,
            $this->user,
            $this->password,
            $this->vhost
        );
    }

    public function consume(string $queue, callable $callback): void
    {
        $channel = $this->getChannel();

        $channel->basic_qos(0, 1, false);

        $channel->basic_consume(
            $queue,
            '',
            false,
            false,
            false,
            false,
            $callback
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    public function close(): void
    {
        $this->channel?->close();
        $this->connection?->close();
    }

    public function __destruct()
    {
        $this->close();
    }
}
