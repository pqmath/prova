<?php

namespace Infrastructure\Console\Commands;

use Application\DTOs\CreateOccurrenceDTO;
use Application\Models\AuditLog;
use Application\Models\EventInbox;
use Application\UseCases\CreateOccurrenceUseCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Infrastructure\Services\RabbitMQ\RabbitMQClient;
use PhpAmqpLib\Message\AMQPMessage;

class ProcessOccurrencesCommand extends Command
{
    protected $signature = 'rabbitmq:consume-occurrences';
    protected $description = 'Consumes messages from the occurrences queue.';

    public function __construct(
        private readonly RabbitMQClient $rabbitMQClient,
        private readonly CreateOccurrenceUseCase $useCase,
        private readonly \Domain\Services\LoggerInterface $logger
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Worker iniciado. Aguardando mensagens na fila "occurrences"...');
        $this->logger->info('Worker iniciado. Aguardando mensagens na fila "occurrences"...');

        $channel = $this->rabbitMQClient->getChannel();
        $channel->queue_declare('occurrences', false, true, false, false);
        $channel->exchange_declare('occurrences', 'topic', false, true, false);
        $channel->queue_bind('occurrences', 'occurrences', '#');

        $callback = function (AMQPMessage $message) {
            $body = json_decode($message->getBody(), true);
            $eventInboxId = $body['event_inbox_id'] ?? null;

            if (!$eventInboxId) {
                $this->warn("Mensagem inválida recebida.");
                $this->logger->warning("Mensagem inválida recebida: " . $message->getBody());
                $message->ack();
                return;
            }

            $this->logger->info("Processando evento {$eventInboxId}...", ['payload' => $body]);

            DB::beginTransaction();
            try {
                $eventInbox = EventInbox::lockForUpdate()->find($eventInboxId);

                if (!$eventInbox) {
                    DB::rollBack();
                    $this->logger->warning("Evento {$eventInboxId} não encontrado no banco.");
                    $message->ack();
                    return;
                }

                if ($eventInbox->status === 'processed') {
                    DB::rollBack();
                    $message->ack();
                    $this->info("Evento {$eventInboxId} já processado.");
                    $this->logger->info("Evento {$eventInboxId} já processado.");
                    return;
                }

                $payload = $body['payload'];

                $externalId = $payload['externalId'] ?? $payload['external_id'] ?? null;
                $reportedAt = $payload['reportedAt'] ?? $payload['reported_at'] ?? null;

                if (!$externalId || !$reportedAt) {
                    $this->logger->warning("Payload incompleto ou inválido.", ['payload' => $payload]);
                    $message->nack();
                    return;
                }

                $dto = new CreateOccurrenceDTO(
                    $externalId,
                    $payload['type'],
                    $payload['description'],
                    $reportedAt
                );

                $result = $this->useCase->execute($dto);
                $occurrence = $result['occurrence'];
                $action = $result['action'];
                $before = $result['before'] ?? null;

                AuditLog::create([
                    'entity_type' => 'Occurrence',
                    'entity_id' => $occurrence->id,
                    'action' => $action,
                    'before' => $before ? (array) $before : null,
                    'after' => (array) $occurrence,
                    'meta' => [
                        'event_inbox_id' => $eventInbox->id,
                        'source' => $eventInbox->source
                    ],
                ]);

                $eventInbox->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                    'error' => null
                ]);

                DB::commit();
                $message->ack();
                $this->info("Evento {$eventInboxId} processado com sucesso. Ocorrência: {$occurrence->id}");
                $this->logger->info("Evento {$eventInboxId} processado com sucesso. Ocorrência: {$occurrence->id}");

            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("Erro ao processar evento {$eventInboxId}: " . $e->getMessage());
                $this->logger->error("Erro ao processar evento {$eventInboxId}: " . $e->getMessage(), ['exception' => $e]);

                try {
                    $eventInbox = EventInbox::find($eventInboxId);
                    if ($eventInbox) {
                        $eventInbox->update([
                            'status' => 'failed',
                            'error' => $e->getMessage(),
                            'processed_at' => now()
                        ]);
                    }
                } catch (\Throwable $inner) {
                    $this->logger->error("Falha ao atualizar status de erro no EventInbox: " . $inner->getMessage());
                }

                $message->nack();
            }
        };

        $this->rabbitMQClient->consume('occurrences', $callback);

        return 0;
    }
}
