<?php

namespace Tests\Unit\Infrastructure\Services\RabbitMQ;

use Infrastructure\Services\RabbitMQ\RabbitMQClient;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Infrastructure\Services\RabbitMQ\AMQPConnectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RabbitMQClientTest extends TestCase
{
    private MockObject|AMQPStreamConnection $connection;
    private MockObject|AMQPChannel $channel;
    private RabbitMQClient $client;

    private MockObject|AMQPConnectionFactory $factory;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(AMQPStreamConnection::class);
        $this->channel = $this->createMock(AMQPChannel::class);
        $this->factory = $this->createMock(AMQPConnectionFactory::class);
        $this->factory->method('create')->willReturn($this->connection);

        $this->client = new RabbitMQClient(
            'host',
            5672,
            'user',
            'pass',
            '/',
            $this->factory
        );
    }

    public function test_get_channel_connects_if_not_connected()
    {
        $this->connection->method('isConnected')->willReturn(false);
        $this->connection->expects($this->once())->method('channel')->willReturn($this->channel);

        $channel = $this->client->getChannel();

        $this->assertSame($this->channel, $channel);
    }

    public function test_get_channel_reuses_connection_and_channel()
    {
        $this->connection->method('isConnected')->willReturn(false);
        $this->connection->expects($this->once())->method('channel')->willReturn($this->channel);

        $this->channel->method('is_open')->willReturn(true);

        $channel1 = $this->client->getChannel();
        $channel2 = $this->client->getChannel();

        $this->assertSame($channel1, $channel2);
    }

    public function test_consume_setup_qos_and_consumes()
    {
        $this->connection->method('isConnected')->willReturn(false);
        $this->connection->method('channel')->willReturn($this->channel);
        $this->channel->method('is_open')->willReturn(true);

        $this->channel->expects($this->once())->method('basic_qos')->with(0, 1, false);
        $this->channel->expects($this->once())->method('basic_consume');
        $this->channel->expects($this->exactly(2))
            ->method('is_consuming')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->channel->expects($this->once())->method('wait');

        $this->client->consume('queue_name', function () {});
    }

    public function test_close_closes_channel_and_connection()
    {
        $this->connection->method('isConnected')->willReturn(false);
        $this->connection->method('channel')->willReturn($this->channel);
        $this->client->getChannel();

        $this->channel->expects($this->once())->method('close');
        $this->connection->expects($this->once())->method('close');

        $this->client->close();
    }
    public function test_destruct_calls_close()
    {
        $this->connection->method('isConnected')->willReturn(false);
        $this->connection->method('channel')->willReturn($this->channel);
        $this->client->getChannel();

        $this->channel->expects($this->once())->method('close');
        $this->connection->expects($this->once())->method('close');

        $this->client->__destruct();
    }
}
