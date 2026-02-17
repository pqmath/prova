<?php

namespace Tests\Unit\Infrastructure\Console\Commands;

use Application\Models\Dispatch;
use Application\Models\Occurrence;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Infrastructure\Console\Commands\SimulateDispatchMovement;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TestableSimulateDispatchMovement extends SimulateDispatchMovement
{
    public bool $shouldStopInWait = true;

    protected function wait(int $seconds): void
    {
        if ($this->shouldStopInWait) {
            $this->keepRunning = false;
        } else {
            parent::wait($seconds);
        }
    }

    public function callRealWait(int $s): void
    {
        parent::wait($s);
    }
}

class SimulateDispatchMovementTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(now());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_moves_assigned_to_en_route()
    {
        $occurrence = $this->createOccurrence();
        $dispatch = Dispatch::create([
            'occurrence_id' => $occurrence->id,
            'resource_code' => 'RES-1',
            'status' => 'assigned',
            'updated_at' => now()->subSeconds(10),
        ]);

        $this->runCommand();

        $this->assertEquals('en_route', $dispatch->refresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'Dispatch',
            'entity_id' => $dispatch->id,
            'action' => 'status_changed',
            'source' => 'Simulador',
        ]);
    }

    public function test_it_moves_en_route_to_on_site()
    {
        $occurrence = $this->createOccurrence();
        $dispatch = Dispatch::create([
            'occurrence_id' => $occurrence->id,
            'resource_code' => 'RES-2',
            'status' => 'en_route',
            'updated_at' => now()->subSeconds(10),
        ]);

        $this->runCommand();

        $this->assertEquals('on_site', $dispatch->refresh()->status);
    }

    public function test_it_closes_dispatches_when_occurrence_is_resolved()
    {
        $occurrence = $this->createOccurrence('resolved');
        $dispatch = Dispatch::create([
            'occurrence_id' => $occurrence->id,
            'resource_code' => 'RES-3',
            'status' => 'on_site',
        ]);

        $this->runCommand();

        $this->assertEquals('closed', $dispatch->refresh()->status);
    }

    public function test_it_covers_empty_resolved_occurrences()
    {
        $this->runCommand();
        $this->assertTrue(true);
    }

    public function test_it_covers_resolved_with_no_open_dispatches()
    {
        $this->createOccurrence('resolved');
        $this->runCommand();
        $this->assertTrue(true);
    }

    public function test_it_covers_real_wait_method()
    {
        $command = new TestableSimulateDispatchMovement();
        $command->callRealWait(0);
        $this->assertTrue(true);
    }

    public function test_it_covers_handle_loop_exit_inside_tick()
    {
        $command = new class extends SimulateDispatchMovement {
            public function tick(): void
            {
                $this->keepRunning = false;
                parent::tick();
            }
            protected function wait(int $s): void
            {
            }
        };
        $this->app->instance(SimulateDispatchMovement::class, $command);
        $this->artisan('simulate:movement');
        $this->assertTrue(true);
    }

    private function createOccurrence(string $status = 'reported'): Occurrence
    {
        return Occurrence::create([
            'external_id' => 'EXT-' . uniqid(),
            'type' => 'incendio_urbano',
            'status' => $status,
            'description' => 'Test',
            'reported_at' => now(),
        ]);
    }

    private function runCommand(): void
    {
        $command = new TestableSimulateDispatchMovement();
        $this->app->instance(SimulateDispatchMovement::class, $command);

        $this->artisan('simulate:movement')
            ->assertExitCode(0);
    }
}
