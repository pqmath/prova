<?php

namespace Tests\Unit\Infrastructure\Services\RabbitMQ;

use Infrastructure\Services\RabbitMQ\RabbitMQClient;
use Infrastructure\Services\RabbitMQ\RabbitMQPublisher;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RabbitMQPublisherTest extends TestCase
{
    private MockObject|RabbitMQClient $client;
    private MockObject|AMQPChannel $channel;
    private RabbitMQPublisher $publisher;

    protected function setUp(): void
    {
        $this->client = $this->createMock(RabbitMQClient::class);
        $this->channel = $this->createMock(AMQPChannel::class);

        $this->client->method('getChannel')->willReturn($this->channel);

        $this->publisher = new RabbitMQPublisher($this->client);
    }

    public function test_publish_declares_exchange_and_publishes_message()
    {
        $exchange = 'test_exchange';
        $routingKey = 'test.key';
        $message = ['data' => 123];

        $this->channel->expects($this->once())
            ->method('exchange_declare')
            ->with($exchange, 'topic', false, true, false);

        $this->channel->expects($this->once())
            ->method('basic_publish')
            ->with(
                $this->callback(function (AMQPMessage $msg) use ($message) {
                    return $msg->getBody() === json_encode($message)
                        && $msg->get_properties()['delivery_mode'] === AMQPMessage::DELIVERY_MODE_PERSISTENT;
                }),
                $exchange,
                $routingKey
            );

        $this->publisher->publish($exchange, $routingKey, $message);
    }
}
