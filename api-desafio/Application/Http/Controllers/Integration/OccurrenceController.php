<?php

namespace Application\Http\Controllers\Integration;

use Application\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Domain\Repositories\EventInboxRepositoryInterface;
use Domain\Entities\EventInbox;

class OccurrenceController extends Controller
{
    public function __construct(
        private readonly EventInboxRepositoryInterface $inboxRepository
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');
        if (!$idempotencyKey) {
            return response()->json(['error' => 'Idempotency-Key header is required'], 400);
        }

        $existing = $this->inboxRepository->findByIdempotencyKey($idempotencyKey);
        if ($existing) {
            return response()->json([
                'message' => 'Event already received',
                'event_id' => $existing->id,
                'status' => $existing->status
            ], 202);
        }

        try {
            $event = EventInbox::create(
                $idempotencyKey,
                'api_integration',
                'occurrence.created',
                $request->all()
            );

            $this->inboxRepository->save($event);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                $existing = $this->inboxRepository->findByIdempotencyKey($idempotencyKey);
                return response()->json([
                    'message' => 'Event already received (Concurrency Handled)',
                    'event_id' => $existing->id,
                    'status' => $existing->status
                ], 202);
            }
            throw $e;
        }

        $savedEvent = $this->inboxRepository->findByIdempotencyKey($idempotencyKey);

        return response()->json([
            'message' => 'Occurrence received successfully',
            'event_id' => $savedEvent->id,
            'status' => 'pending'
        ], 202);
    }
}
