<?php

namespace Application\UseCases;

use Application\Models\EventInbox;
use Domain\Services\LoggerInterface;
use Illuminate\Database\QueryException;

class RequestCommandUseCase
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(
        string $idempotencyKey,
        string $source,
        string $type,
        array $payload
    ): string {
        try {
            $event = EventInbox::create([
                'idempotency_key' => $idempotencyKey,
                'source' => $source,
                'type' => $type,
                'payload' => $payload,
                'status' => 'pending',
                'publish_attempts' => 0,
            ]);

            $this->logger->info('EventInbox created', [
                'event_inbox_id' => $event->id,
                'idempotency_key' => $idempotencyKey,
                'source' => $source,
                'type' => $type,
            ]);

            return $event->id;

        } catch (QueryException $e) {
            if ($this->isDuplicateKeyError($e)) {
                $this->logger->warning('Duplicate key detected (race condition)', [
                    'idempotency_key' => $idempotencyKey,
                    'source' => $source,
                    'type' => $type,
                    'error_code' => $e->getCode(),
                ]);

                $existing = EventInbox::where('idempotency_key', $idempotencyKey)
                    ->where('source', $source)
                    ->first();

                if ($existing) {
                    return $existing->id;
                }
            }

            $this->logger->error('Failed to create EventInbox', [
                'idempotency_key' => $idempotencyKey,
                'source' => $source,
                'type' => $type,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            throw $e;
        }
    }

    private function isDuplicateKeyError(QueryException $e): bool
    {
        $errorCode = $e->getCode();
        $errorMessage = strtolower($e->getMessage());

        return $errorCode == 23505
            || $errorCode == 23000
            || str_contains($errorMessage, 'unique')
            || str_contains($errorMessage, 'duplicate');
    }
}
