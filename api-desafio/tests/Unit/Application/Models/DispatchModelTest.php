<?php

namespace Tests\Unit\Application\Models;

use Application\Models\Dispatch;
use Application\Models\Occurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_belongs_to_occurrence()
    {
        $occurrence = Occurrence::create([
            'external_id' => 'EXT-123',
            'type' => 'incendio_urbano',
            'status' => 'reported',
            'description' => 'Incêndio em test',
            'reported_at' => now(),
        ]);

        $dispatch = Dispatch::create([
            'occurrence_id' => $occurrence->id,
            'resource_code' => 'ABT-01',
            'status' => 'assigned',
        ]);

        $this->assertInstanceOf(Occurrence::class, $dispatch->occurrence);
        $this->assertEquals($occurrence->id, $dispatch->occurrence->id);
    }
}
