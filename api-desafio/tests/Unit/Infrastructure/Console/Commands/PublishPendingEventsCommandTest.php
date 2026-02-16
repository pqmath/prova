<?php

namespace Tests\Unit\Infrastructure\Console\Commands;

use DateTimeImmutable;
use Domain\Entities\EventInbox as DomainEventInbox;
use Domain\Repositories\EventInboxRepositoryInterface;
use Domain\Services\LoggerInterface;
use Domain\Services\MessageBrokerInterface;
use Illuminate\Console\OutputStyle;
use Infrastructure\Console\Commands\PublishPendingEventsCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class PublishPendingEventsCommandTest extends TestCase
{
    public function test_handle_publishes_pending_events_and_updates_status()
    {
        $repo = $this->createMock(EventInboxRepositoryInterface::class);
        $broker = $this->createMock(MessageBrokerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $event = new DomainEventInbox(
            'evt-id-1',
            'key-pub-1',
            'source-pub',
            'type.pub',
            ['data' => 1],
            'pending',
            null,
            null,
            0,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );

        $repo->expects($this->once())
            ->method('findPending')
            ->with(50)
            ->willReturn([$event]);

        $broker->expects($this->once())
            ->method('publish')
            ->with(
                'occurrences',
                'type.pub',
                [
                    'idempotency_key' => 'key-pub-1',
                    'payload' => ['data' => 1],
                    'source' => 'source-pub',
                    'event_inbox_id' => $event->id
                ]
            );

        $repo->expects($this->once())
            ->method('updateStatus')
            ->with($event->id, 'published');

        $command = new PublishPendingEventsCommand($repo, $broker, $logger);

        $command->setLaravel(app());
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->setOutput(new OutputStyle($input, $output));

        $result = $command->handle();

        $this->assertEquals(0, $result);
    }

    public function test_handle_increments_attempts_on_failure()
    {
        $repo = $this->createMock(EventInboxRepositoryInterface::class);
        $broker = $this->createMock(MessageBrokerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $event = new DomainEventInbox(
            'evt-id-fail-1',
            'key-fail-1',
            'source-fail',
            'type.fail',
            [],
            'pending',
            null,
            null,
            0,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );

        $repo->expects($this->once())
            ->method('findPending')
            ->willReturn([$event]);

        $broker->expects($this->once())
            ->method('publish')
            ->willThrowException(new \Exception('Broker error'));

        $repo->expects($this->once())
            ->method('incrementAttempts')
            ->with($event->id);

        $logger->expects($this->once())
            ->method('error');

        $command = new PublishPendingEventsCommand($repo, $broker, $logger);

        $command->setLaravel(app());
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->setOutput(new OutputStyle($input, $output));

        $result = $command->handle();

        $this->assertEquals(0, $result);
    }

    public function test_handle_returns_zero_when_no_pending_events()
    {
        $repo = $this->createMock(EventInboxRepositoryInterface::class);
        $broker = $this->createMock(MessageBrokerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $repo->expects($this->once())
            ->method('findPending')
            ->willReturn([]);

        $broker->expects($this->never())->method('publish');
        $logger->expects($this->never())->method('info');

        $command = new PublishPendingEventsCommand($repo, $broker, $logger);

        $command->setLaravel(app());
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->setOutput(new OutputStyle($input, $output));

        $result = $command->handle();

        $this->assertEquals(0, $result);
    }
}
