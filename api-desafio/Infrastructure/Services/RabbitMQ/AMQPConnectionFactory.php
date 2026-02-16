<?php

namespace Infrastructure\Services\RabbitMQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class AMQPConnectionFactory
{
    public function create(
        string $host,
        int $port,
        string $user,
        string $password,
        string $vhost = '/'
    ): AMQPStreamConnection {
        return new AMQPStreamConnection($host, $port, $user, $password, $vhost);
    }
}
