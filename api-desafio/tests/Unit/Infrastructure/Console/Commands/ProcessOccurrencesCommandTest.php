<?php

namespace Tests\Unit\Infrastructure\Console\Commands;

use Application\DTOs\CreateOccurrenceDTO;
use Application\Models\EventInbox;
use Application\UseCases\CreateDispatchUseCase;
use Application\UseCases\CreateOccurrenceUseCase;
use Application\UseCases\ResolveOccurrenceUseCase;
use Application\UseCases\StartOccurrenceUseCase;
use Domain\Services\LoggerInterface;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Console\Commands\ProcessOccurrencesCommand;
use Infrastructure\Services\RabbitMQ\RabbitMQClient;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class ProcessOccurrencesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_consumes_message_and_processes_creation()
    {
        $event = EventInbox::create([
            'idempotency_key' => 'key-cmd-1',
            'source' => 'test',
            'type' => 'occurrence.received',
            'payload' => [
                'externalId' => 'EXT-CMD-1',
                'type' => 'incendio_urbano',
                'description' => 'Desc',
                'reportedAt' => '2026-01-01 10:00:00'
            ],
            'status' => 'pending'
        ]);

        $rabbitMQClient = $this->createMock(RabbitMQClient::class);
        $channel = $this->createMock(AMQPChannel::class);
        $createUseCase = $this->createMock(CreateOccurrenceUseCase::class);
        $startUseCase = $this->createMock(StartOccurrenceUseCase::class);
        $resolveUseCase = $this->createMock(ResolveOccurrenceUseCase::class);
        $dispatchUseCase = $this->createMock(CreateDispatchUseCase::class);
        $logger = $this->createMock(LoggerInterface::class);

        $rabbitMQClient->method('getChannel')->willReturn($channel);

        $rabbitMQClient->expects($this->once())
            ->method('consume')
            ->willReturnCallback(function ($queue, $callback) use ($event) {
                $body = json_encode([
                    'event_inbox_id' => $event->id,
                    'type' => 'occurrence.created',
                    'payload' => $event->payload
                ]);
                $msg = $this->createMock(AMQPMessage::class);
                $msg->method('getBody')->willReturn($body);
                $msg->method('getRoutingKey')->willReturn('occurrence.created');
                $msg->expects($this->once())->method('ack');

                $callback($msg);
            });

        $createUseCase->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (CreateOccurrenceDTO $dto) {
                return $dto->externalId === 'EXT-CMD-1';
            }))
            ->willReturn([
                'action' => 'created',
                'occurrence' => (object) ['id' => 'occ-id-1']
            ]);

        $command = new ProcessOccurrencesCommand(
            $rabbitMQClient,
            $createUseCase,
            $startUseCase,
            $resolveUseCase,
            $dispatchUseCase,
            $logger
        );

        $command->setLaravel(app());
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->setOutput(new OutputStyle($input, $output));

        $command->handle();

        $this->assertDatabaseHas('event_inboxes', [
            'id' => $event->id,
            'status' => 'processed'
        ]);
    }

    public function test_handle_consumes_start_event()
    {
        $event = EventInbox::create([
            'idempotency_key' => 'key-start-1',
            'source' => 'test',
            'type' => 'occurrence.started',
            'payload' => ['id' => 'occ-id-1'],
            'status' => 'pending'
        ]);

        $this->process_event($event, 'occurrence.started', StartOccurrenceUseCase::class);
    }

    public function test_handle_consumes_resolve_event()
    {
        $event = EventInbox::create([
            'idempotency_key' => 'key-resolve-1',
            'source' => 'test',
            'type' => 'occurrence.resolved',
            'payload' => ['id' => 'occ-id-1'],
            'status' => 'pending'
        ]);

        $this->process_event($event, 'occurrence.resolved', ResolveOccurrenceUseCase::class);
    }

    public function test_handle_consumes_dispatch_event()
    {
        $event = EventInbox::create([
            'idempotency_key' => 'key-dispatch-1',
            'source' => 'test',
            'type' => 'dispatch.requested',
            'payload' => ['occurrence_id' => 'occ-id-1', 'resource_code' => 'RES-1'],
            'status' => 'pending'
        ]);

        $this->process_event($event, 'dispatch.requested', CreateDispatchUseCase::class);
    }

    private function process_event($event, $routingKey, $useCaseClass)
    {
        $rabbitMQClient = $this->createMock(RabbitMQClient::class);
        $channel = $this->createMock(AMQPChannel::class);
        $createUseCase = $this->createMock(CreateOccurrenceUseCase::class);
        $startUseCase = $this->createMock(StartOccurrenceUseCase::class);
        $resolveUseCase = $this->createMock(ResolveOccurrenceUseCase::class);
        $dispatchUseCase = $this->createMock(CreateDispatchUseCase::class);
        $logger = $this->createMock(LoggerInterface::class);

        $rabbitMQClient->method('getChannel')->willReturn($channel);

        $rabbitMQClient->expects($this->once())
            ->method('consume')
            ->willReturnCallback(function ($queue, $callback) use ($event, $routingKey) {
                $body = json_encode([
                    'event_inbox_id' => $event->id,
                    'type' => $routingKey,
                    'payload' => $event->payload
                ]);
                $msg = $this->createMock(AMQPMessage::class);
                $msg->method('getBody')->willReturn($body);
                $msg->method('getRoutingKey')->willReturn($routingKey);
                $msg->expects($this->once())->method('ack');
                $callback($msg);
            });

        if ($useCaseClass === StartOccurrenceUseCase::class) {
            $startUseCase->expects($this->once())
                ->method('execute')
                ->with($event->payload['id'], $event->source);
        } elseif ($useCaseClass === ResolveOccurrenceUseCase::class) {
            $resolveUseCase->expects($this->once())
                ->method('execute')
                ->with($event->payload['id'], $event->source);
        } elseif ($useCaseClass === CreateDispatchUseCase::class) {
            $dispatchUseCase->expects($this->once())
                ->method('execute')
                ->with($event->payload['occurrence_id'], $event->payload['resource_code'], $event->source);
        }

        $command = new ProcessOccurrencesCommand(
            $rabbitMQClient,
            $createUseCase,
            $startUseCase,
            $resolveUseCase,
            $dispatchUseCase,
            $logger
        );

        $command->setLaravel(app());
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->setOutput(new OutputStyle($input, $output));

        $command->handle();

        $this->assertDatabaseHas('event_inboxes', [
            'id' => $event->id,
            'status' => 'processed'
        ]);
    }

    public function test_handle_logs_error_and_nacks_with_requeue_on_first_failure()
    {
        $event = EventInbox::create([
            'idempotency_key' => 'key-fail-1',
            'source' => 'test',
            'type' => 'occurrence.received',
            'payload' => [],
            'status' => 'pending',
            'publish_attempts' => 0
        ]);

        $rabbitMQClient = $this->createMock(RabbitMQClient::class);
        $channel = $this->createMock(AMQPChannel::class);
        $createUseCase = $this->createMock(CreateOccurrenceUseCase::class);
        $logger = $this->createMock(LoggerInterface::class);

        $rabbitMQClient->method('getChannel')->willReturn($channel);

        $rabbitMQClient->expects($this->once())
            ->method('consume')
            ->willReturnCallback(function ($queue, $callback) use ($event) {
                $body = json_encode([
                    'event_inbox_id' => $event->id,
                    'type' => 'occurrence.received',
                    'payload' => []
                ]);
                $msg = $this->createMock(AMQPMessage::class);
                $msg->method('getBody')->willReturn($body);
                $msg->method('getRoutingKey')->willReturn('occurrence.received');

                // Expect nack(true) for requeue
                $msg->expects($this->once())->method('nack')->with(true);
                $msg->expects($this->never())->method('ack');

                $callback($msg);
            });

        $logger->expects($this->once())->method('warning');

        $command = new ProcessOccurrencesCommand(
            $rabbitMQClient,
            $createUseCase,
            $this->createMock(StartOccurrenceUseCase::class),
            $this->createMock(ResolveOccurrenceUseCase::class),
            $this->createMock(CreateDispatchUseCase::class),
            $logger
        );

        $command->setLaravel(app());
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->setOutput(new OutputStyle($input, $output));

        $command->handle();

        $this->assertDatabaseHas('event_inboxes', [
            'id' => $event->id,
            'status' => 'pending',
            'publish_attempts' => 1
        ]);
    }

    public function test_handle_marks_as_failed_and_acks_after_3_attempts()
    {
        $event = EventInbox::create([
            'idempotency_key' => 'key-fail-3',
            'source' => 'test',
            'type' => 'occurrence.received',
            'payload' => [],
            'status' => 'pending',
            'publish_attempts' => 2 // This execution will be the 3rd attempt
        ]);

        $rabbitMQClient = $this->createMock(RabbitMQClient::class);
        $channel = $this->createMock(AMQPChannel::class);
        $createUseCase = $this->createMock(CreateOccurrenceUseCase::class);
        $logger = $this->createMock(LoggerInterface::class);

        $rabbitMQClient->method('getChannel')->willReturn($channel);

        $rabbitMQClient->expects($this->once())
            ->method('consume')
            ->willReturnCallback(function ($queue, $callback) use ($event) {
                $body = json_encode([
                    'event_inbox_id' => $event->id,
                    'type' => 'occurrence.received',
                    'payload' => []
                ]);
                $msg = $this->createMock(AMQPMessage::class);
                $msg->method('getBody')->willReturn($body);
                $msg->method('getRoutingKey')->willReturn('occurrence.received');

                // Expect ack() because it failed max attempts
                $msg->expects($this->once())->method('ack');
                $msg->expects($this->never())->method('nack');

                $callback($msg);
            });

        $logger->expects($this->once())->method('error');

        $command = new ProcessOccurrencesCommand(
            $rabbitMQClient,
            $createUseCase,
            $this->createMock(StartOccurrenceUseCase::class),
            $this->createMock(ResolveOccurrenceUseCase::class),
            $this->createMock(CreateDispatchUseCase::class),
            $logger
        );

        $command->setLaravel(app());
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->setOutput(new OutputStyle($input, $output));

        $command->handle();

        $this->assertDatabaseHas('event_inboxes', [
            'id' => $event->id,
            'status' => 'failed',
            'publish_attempts' => 3
        ]);
    }

    public function test_handle_acks_invalid_message_without_event_id()
    {
        $rabbitMQClient = $this->createMock(RabbitMQClient::class);
        $channel = $this->createMock(AMQPChannel::class);
        $logger = $this->createMock(LoggerInterface::class);

        $rabbitMQClient->method('getChannel')->willReturn($channel);

        $rabbitMQClient->expects($this->once())
            ->method('consume')
            ->willReturnCallback(function ($queue, $callback) {
                $body = json_encode(['type' => 'test']);
                $msg = $this->createMock(AMQPMessage::class);
                $msg->method('getBody')->willReturn($body);
                $msg->method('getRoutingKey')->willReturn('test');

                $msg->expects($this->once())->method('ack');
                $callback($msg);
            });

        $logger->expects($this->once())->method('warning')->with($this->stringContains('Mensagem inválida'));

        $command = new ProcessOccurrencesCommand(
            $rabbitMQClient,
            $this->createMock(CreateOccurrenceUseCase::class),
            $this->createMock(StartOccurrenceUseCase::class),
            $this->createMock(ResolveOccurrenceUseCase::class),
            $this->createMock(CreateDispatchUseCase::class),
            $logger
        );

        $command->setLaravel(app());
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->setOutput(new OutputStyle($input, $output));

        $command->handle();
    }

    public function test_handle_consumes_message_with_type_in_payload()
    {
        $event = EventInbox::create([
            'idempotency_key' => 'key-nested-type',
            'source' => 'test',
            'type' => 'occurrences',
            'payload' => [
                'type' => 'occurrence.resolved',
                'id' => 'occ-id-nested'
            ],
            'status' => 'pending'
        ]);

        $rabbitMQClient = $this->createMock(RabbitMQClient::class);
        $channel = $this->createMock(AMQPChannel::class);
        $resolveUseCase = $this->createMock(ResolveOccurrenceUseCase::class);
        $logger = $this->createMock(LoggerInterface::class);

        $rabbitMQClient->method('getChannel')->willReturn($channel);

        $rabbitMQClient->expects($this->once())
            ->method('consume')
            ->willReturnCallback(function ($queue, $callback) use ($event) {
                $body = json_encode([
                    'event_inbox_id' => $event->id,
                    'type' => null,
                    'payload' => $event->payload
                ]);
                $msg = $this->createMock(AMQPMessage::class);
                $msg->method('getBody')->willReturn($body);
                $msg->method('getRoutingKey')->willReturn('occurrences');
                $msg->expects($this->once())->method('ack');
                $callback($msg);
            });

        $resolveUseCase->expects($this->once())->method('execute');

        $command = new ProcessOccurrencesCommand(
            $rabbitMQClient,
            $this->createMock(CreateOccurrenceUseCase::class),
            $this->createMock(StartOccurrenceUseCase::class),
            $resolveUseCase,
            $this->createMock(CreateDispatchUseCase::class),
            $logger
        );

        $command->setLaravel(app());
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->setOutput(new OutputStyle($input, $output));

        $command->handle();

        $this->assertDatabaseHas('event_inboxes', [
            'id' => $event->id,
            'status' => 'processed'
        ]);
    }

    public function test_handle_consumes_dispatch_created_alias()
    {
        $event = EventInbox::create([
            'idempotency_key' => 'key-dispatch-created',
            'source' => 'test',
            'type' => 'dispatch.created',
            'payload' => ['occurrence_id' => 'occ-id-1', 'resource_code' => 'RES-1'],
            'status' => 'pending'
        ]);

        $this->process_event($event, 'dispatch.created', CreateDispatchUseCase::class);
    }

    public function test_handle_unknown_event_type()
    {
        $event = EventInbox::create([
            'idempotency_key' => 'key-unknown',
            'source' => 'test',
            'type' => 'unknown.type',
            'payload' => [],
            'status' => 'pending'
        ]);

        $rabbitMQClient = $this->createMock(RabbitMQClient::class);
        $channel = $this->createMock(AMQPChannel::class);
        $logger = $this->createMock(LoggerInterface::class);

        $rabbitMQClient->method('getChannel')->willReturn($channel);

        $rabbitMQClient->expects($this->once())
            ->method('consume')
            ->willReturnCallback(function ($queue, $callback) use ($event) {
                $body = json_encode([
                    'event_inbox_id' => $event->id,
                    'type' => 'unknown.type',
                    'payload' => []
                ]);
                $msg = $this->createMock(AMQPMessage::class);
                $msg->method('getBody')->willReturn($body);
                $msg->method('getRoutingKey')->willReturn('unknown.type');
                $msg->expects($this->once())->method('ack');
                $callback($msg);
            });

        $logger->expects($this->once())->method('warning')->with($this->stringContains('desconhecido'));

        $command = new ProcessOccurrencesCommand(
            $rabbitMQClient,
            $this->createMock(CreateOccurrenceUseCase::class),
            $this->createMock(StartOccurrenceUseCase::class),
            $this->createMock(ResolveOccurrenceUseCase::class),
            $this->createMock(CreateDispatchUseCase::class),
            $logger
        );

        $command->setLaravel(app());
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->setOutput(new OutputStyle($input, $output));

        $command->handle();
    }

    public function test_handle_acks_if_event_not_found()
    {
        $nonExistentId = '12345678-1234-1234-1234-123456789012';

        $rabbitMQClient = $this->createMock(RabbitMQClient::class);
        $channel = $this->createMock(AMQPChannel::class);
        $logger = $this->createMock(LoggerInterface::class);

        $rabbitMQClient->method('getChannel')->willReturn($channel);

        $rabbitMQClient->expects($this->once())
            ->method('consume')
            ->willReturnCallback(function ($queue, $callback) use ($nonExistentId) {
                $body = json_encode([
                    'event_inbox_id' => $nonExistentId,
                    'type' => 'occurrence.received',
                    'payload' => []
                ]);
                $msg = $this->createMock(AMQPMessage::class);
                $msg->method('getBody')->willReturn($body);
                $msg->method('getRoutingKey')->willReturn('occurrence.received');

                $msg->expects($this->once())->method('ack');
                $callback($msg);
            });

        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('não encontrado no banco'));

        $command = new ProcessOccurrencesCommand(
            $rabbitMQClient,
            $this->createMock(CreateOccurrenceUseCase::class),
            $this->createMock(StartOccurrenceUseCase::class),
            $this->createMock(ResolveOccurrenceUseCase::class),
            $this->createMock(CreateDispatchUseCase::class),
            $logger
        );

        $command->setLaravel(app());
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->setOutput(new OutputStyle($input, $output));

        $command->handle();
    }

    public function test_handle_nacks_on_invalid_start_payload()
    {
        $this->assert_nack_on_invalid_payload('occurrence.started', []);
    }

    public function test_handle_nacks_on_invalid_resolve_payload()
    {
        $this->assert_nack_on_invalid_payload('occurrence.resolved', []);
    }

    public function test_handle_nacks_on_invalid_dispatch_payload()
    {
        $this->assert_nack_on_invalid_payload('dispatch.requested', ['occurrence_id' => '1']);
    }

    private function assert_nack_on_invalid_payload(string $type, array $payload)
    {
        $event = EventInbox::create([
            'idempotency_key' => 'key-invalid-' . $type,
            'source' => 'test',
            'type' => $type,
            'payload' => $payload,
            'status' => 'pending',
            'publish_attempts' => 0
        ]);

        // Mocks
        $rabbitMQClient = $this->createMock(RabbitMQClient::class);
        $channel = $this->createMock(AMQPChannel::class);
        $logger = $this->createMock(LoggerInterface::class);

        $rabbitMQClient->method('getChannel')->willReturn($channel);

        $rabbitMQClient->expects($this->once())
            ->method('consume')
            ->willReturnCallback(function ($queue, $callback) use ($event, $type, $payload) {
                $body = json_encode([
                    'event_inbox_id' => $event->id,
                    'type' => $type,
                    'payload' => $payload
                ]);
                $msg = $this->createMock(AMQPMessage::class);
                $msg->method('getBody')->willReturn($body);
                $msg->method('getRoutingKey')->willReturn($type);

                // Expect nack(true) for retry
                $msg->expects($this->once())->method('nack')->with(true);
                $callback($msg);
            });

        $logger->expects($this->atLeastOnce())->method('warning');

        $command = new ProcessOccurrencesCommand(
            $rabbitMQClient,
            $this->createMock(CreateOccurrenceUseCase::class),
            $this->createMock(StartOccurrenceUseCase::class),
            $this->createMock(ResolveOccurrenceUseCase::class),
            $this->createMock(CreateDispatchUseCase::class),
            $logger
        );

        $command->setLaravel(app());
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->setOutput(new OutputStyle($input, $output));

        $command->handle();

        $this->assertDatabaseHas('event_inboxes', [
            'id' => $event->id,
            'status' => 'pending',
            'publish_attempts' => 1
        ]);
    }

    public function test_handle_acks_if_event_already_processed()
    {
        $event = EventInbox::create([
            'idempotency_key' => 'key-already-processed',
            'source' => 'test',
            'type' => 'occurrence.received',
            'payload' => [
                'externalId' => 'EXT-1',
                'type' => 'incendio_urbano',
                'description' => 'Test',
                'reportedAt' => '2026-01-01 10:00:00'
            ],
            'status' => 'processed',
            'processed_at' => now()
        ]);

        $rabbitMQClient = $this->createMock(RabbitMQClient::class);
        $channel = $this->createMock(AMQPChannel::class);
        $createUseCase = $this->createMock(CreateOccurrenceUseCase::class);
        $logger = $this->createMock(LoggerInterface::class);

        $rabbitMQClient->method('getChannel')->willReturn($channel);

        $rabbitMQClient->expects($this->once())
            ->method('consume')
            ->willReturnCallback(function ($queue, $callback) use ($event) {
                $body = json_encode([
                    'event_inbox_id' => $event->id,
                    'type' => 'occurrence.received',
                    'payload' => $event->payload
                ]);
                $msg = $this->createMock(AMQPMessage::class);
                $msg->method('getBody')->willReturn($body);
                $msg->method('getRoutingKey')->willReturn('occurrence.received');

                $msg->expects($this->once())->method('ack');
                $callback($msg);
            });

        $createUseCase->expects($this->never())->method('execute');

        $alreadyProcessedLogged = false;
        $logger->expects($this->atLeastOnce())
            ->method('info')
            ->willReturnCallback(function ($message) use (&$alreadyProcessedLogged, $event) {
                if (
                    str_contains($message, 'já processado') ||
                    str_contains($message, "Evento {$event->id} já processado")
                ) {
                    $alreadyProcessedLogged = true;
                }
            });

        $command = new ProcessOccurrencesCommand(
            $rabbitMQClient,
            $createUseCase,
            $this->createMock(StartOccurrenceUseCase::class),
            $this->createMock(ResolveOccurrenceUseCase::class),
            $this->createMock(CreateDispatchUseCase::class),
            $logger
        );

        $command->setLaravel(app());
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->setOutput(new OutputStyle($input, $output));

        $command->handle();

        $this->assertDatabaseHas('event_inboxes', [
            'id' => $event->id,
            'status' => 'processed'
        ]);

        $this->assertTrue($alreadyProcessedLogged, 'Failed asserting that "já processado" was logged.');
    }
}
