<?php

namespace Tests\Unit\Infrastructure\Services\RabbitMQ;

use Exception;
use Infrastructure\Services\RabbitMQ\AMQPConnectionFactory;
use PHPUnit\Framework\TestCase;

class AMQPConnectionFactoryTest extends TestCase
{
    public function test_create_attempts_connection()
    {
        $factory = new AMQPConnectionFactory();

        $this->expectException(Exception::class);

        $factory->create('invalid-host', 5672, 'user', 'pass');
    }
}
