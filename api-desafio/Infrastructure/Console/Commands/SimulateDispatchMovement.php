<?php

namespace Infrastructure\Console\Commands;

use Illuminate\Console\Command;
use Application\Models\Dispatch;
use Application\Models\Occurrence;
use Illuminate\Support\Carbon;
use Application\Models\AuditLog;

class SimulateDispatchMovement extends Command
{
    protected $signature = 'simulate:movement';
    protected $description = 'Simulates the movement of resources (assigned -> en_route -> on_site -> closed)';

    protected bool $keepRunning = true;

    public function handle(): void
    {
        $this->info("Simulating Dispatch Movement...");

        while ($this->keepRunning) {
            $this->tick();
            if ($this->keepRunning) {
                $this->wait(2);
            }
        }
    }

    public function tick(): void
    {
        $now = Carbon::now();

        $assigned = Dispatch::where('status', 'assigned')
            ->where('updated_at', '<=', $now->copy()->subSeconds(5))
            ->get();

        foreach ($assigned as $dispatch) {
            $before = $dispatch->toArray();
            $dispatch->update(['status' => 'en_route']);

            AuditLog::create([
                'entity_type' => 'Dispatch',
                'entity_id' => $dispatch->id,
                'action' => 'status_changed',
                'source' => 'Simulador',
                'before' => $before,
                'after' => $dispatch->refresh()->toArray(),
            ]);

            $this->info("Dispatch {$dispatch->id} ({$dispatch->resource_code}) moved to EN_ROUTE");
        }

        $enRoute = Dispatch::where('status', 'en_route')
            ->where('updated_at', '<=', $now->copy()->subSeconds(5))
            ->get();

        foreach ($enRoute as $dispatch) {
            $before = $dispatch->toArray();
            $dispatch->update(['status' => 'on_site']);

            AuditLog::create([
                'entity_type' => 'Dispatch',
                'entity_id' => $dispatch->id,
                'action' => 'status_changed',
                'source' => 'Simulador',
                'before' => $before,
                'after' => $dispatch->refresh()->toArray(),
            ]);

            $this->info("Dispatch {$dispatch->id} ({$dispatch->resource_code}) moved to ON_SITE");
        }

        $resolvedOccurrences = Occurrence::where('status', 'resolved')->pluck('id');

        if ($resolvedOccurrences->isNotEmpty()) {
            $openDispatches = Dispatch::whereIn('occurrence_id', $resolvedOccurrences)
                ->whereNotIn('status', ['closed', 'cancelled'])
                ->get();

            foreach ($openDispatches as $d) {
                $before = $d->toArray();
                $d->update(['status' => 'closed']);

                AuditLog::create([
                    'entity_type' => 'Dispatch',
                    'entity_id' => $d->id,
                    'action' => 'status_changed',
                    'source' => 'Simulador',
                    'before' => $before,
                    'after' => $d->refresh()->toArray(),
                ]);

                $this->info("Dispatch {$d->id} CLOSED (Occurrence Resolved)");
            }
        }
    }

    protected function wait(int $seconds): void
    {
        sleep($seconds);
    }
}
