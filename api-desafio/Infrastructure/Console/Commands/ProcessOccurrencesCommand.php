<?php

namespace Infrastructure\Console\Commands;

use Application\DTOs\CreateOccurrenceDTO;
use Application\Models\AuditLog;
use Application\Models\EventInbox;
use Application\UseCases\CreateDispatchUseCase;
use Application\UseCases\CreateOccurrenceUseCase;
use Application\UseCases\ResolveOccurrenceUseCase;
use Application\UseCases\StartOccurrenceUseCase;
use Domain\Services\LoggerInterface;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Infrastructure\Services\RabbitMQ\RabbitMQClient;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class ProcessOccurrencesCommand extends Command
{
    protected $signature = 'rabbitmq:consume-occurrences';
    protected $description = 'Consumes messages from the occurrences queue.';

    public function __construct(
        private readonly RabbitMQClient $rabbitMQClient,
        private readonly CreateOccurrenceUseCase $createUseCase,
        private readonly StartOccurrenceUseCase $startUseCase,
        private readonly ResolveOccurrenceUseCase $resolveUseCase,
        private readonly CreateDispatchUseCase $dispatchUseCase,
        private readonly LoggerInterface $logger
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
            $type = $message->getRoutingKey();

            if (empty($type) || $type === 'occurrences') {
                $type = $body['type'] ?? $body['payload']['type'] ?? null;
            }

            if (!$eventInboxId) {
                $this->warn("Mensagem inválida recebida (sem event_inbox_id).");
                $this->logger->warning("Mensagem inválida recebida: " . $message->getBody());
                $message->ack();
                return;
            }

            $this->logger->info("Processando evento {$eventInboxId} do tipo '{$type}'...", ['payload' => $body]);

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
                    $this->logger->info("Evento {$eventInboxId} já processado.");
                    return;
                }

                $payload = $body['payload'] ?? [];

                switch ($type) {
                    case 'occurrence.created':
                    case 'occurrence.received':
                        $this->processCreation($payload, $eventInbox);
                        break;
                    case 'occurrence.started':
                        $this->processStart($payload);
                        break;
                    case 'occurrence.resolved':
                        $this->processResolve($payload);
                        break;
                    case 'dispatch.requested':
                    case 'dispatch.created':
                        $this->processDispatch($payload);
                        break;
                    default:
                        $this->logger->warning("Tipo de evento desconhecido: {$type}", ['event_inbox_id' => $eventInboxId]);
                        break;
                }

                $eventInbox->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                    'error' => null
                ]);

                DB::commit();
                $message->ack();
                $this->info("Evento {$eventInboxId} ({$type}) processado com sucesso.");
                $this->logger->info("Evento {$eventInboxId} ({$type}) processado com sucesso.");

            } catch (Throwable $e) {
                DB::rollBack();
                $this->error("Erro ao processar evento {$eventInboxId}: " . $e->getMessage());
                $this->logger->error(
                    "Erro ao processar evento {$eventInboxId}: " . $e->getMessage(), ['exception' => $e]
                );

                try {
                    $eventInbox = EventInbox::find($eventInboxId);
                    if ($eventInbox) {
                        $eventInbox->update([
                            'status' => 'failed',
                            'error' => $e->getMessage(),
                            'processed_at' => now()
                        ]);
                    }
                } catch (Throwable $inner) {
                    $this->logger->error("Falha ao atualizar status de erro no EventInbox: " . $inner->getMessage());
                }

                $message->nack();
            }
        };

        $this->rabbitMQClient->consume('occurrences', $callback);

        return 0;
    }

    private function processCreation(array $payload, EventInbox $eventInbox): void
    {
        $externalId = $payload['externalId'] ?? $payload['external_id'] ?? null;
        $reportedAt = $payload['reportedAt'] ?? $payload['reported_at'] ?? null;

        if (!$externalId || !$reportedAt) {
            throw new Exception("Payload inválido para criação: externalId e reportedAt são obrigatórios.");
        }

        $dto = new CreateOccurrenceDTO(
            $externalId,
            $payload['type'],
            $payload['description'],
            $reportedAt
        );

        $result = $this->createUseCase->execute($dto);
        $occurrence = $result['occurrence'];

        // Log Audit
        AuditLog::create([
            'entity_type' => 'Occurrence',
            'entity_id' => $occurrence->id,
            'action' => $result['action'],
            'before' => null,
            'after' => (array) $occurrence,
            'meta' => [
                'event_inbox_id' => $eventInbox->id,
                'source' => $eventInbox->source
            ],
        ]);
    }

    private function processStart(array $payload): void
    {
        $id = $payload['id'] ?? null;
        if (!$id)
            throw new Exception("Payload inválido para start: id é obrigatório.");

        $this->startUseCase->execute($id);
    }

    private function processResolve(array $payload): void
    {
        $id = $payload['id'] ?? null;
        if (!$id)
            throw new Exception("Payload inválido para resolve: id é obrigatório.");

    }

    private function processDispatch(array $payload): void
    {
        $occurrenceId = $payload['occurrence_id'] ?? null;
        $resourceCode = $payload['resource_code'] ?? null;

        if (!$occurrenceId || !$resourceCode) {
            throw new Exception("Payload inválido para despacho: occurrence_id e resource_code são obrigatórios.");
        }

        $this->dispatchUseCase->execute($occurrenceId, $resourceCode);
    }
}
