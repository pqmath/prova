<?php

namespace Infrastructure\Services\RabbitMQ;

use Domain\Services\MessageBrokerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQPublisher implements MessageBrokerInterface
{
    public function __construct(
        private readonly RabbitMQClient $client
    ) {
    }

    public function publish(string $exchange, string $routingKey, array $message): void
    {
        $channel = $this->client->getChannel();

        $channel->exchange_declare($exchange, 'topic', false, true, false);

        $msg = new AMQPMessage(
            json_encode($message),
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );

        $channel->basic_publish($msg, $exchange, $routingKey);
    }
}
