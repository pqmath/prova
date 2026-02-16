<?php

namespace Tests\Unit\Application\UseCases;

use Application\Models\AuditLog;
use Application\UseCases\ListAuditLogsUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListAuditLogsUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private ListAuditLogsUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCase = new ListAuditLogsUseCase();
    }

    public function test_execute_returns_paginated_logs()
    {
        $log1 = AuditLog::create([
            'entity_type' => 'Occurrence',
            'entity_id' => '1',
            'action' => 'created',
            'source' => 'Sistema',
            'after' => ['status' => 'reported'],
        ]);
        $log1->created_at = now()->subMinute();
        $log1->save();

        $log2 = AuditLog::create([
            'entity_type' => 'Occurrence',
            'entity_id' => '2',
            'action' => 'started',
            'source' => 'Sistema',
            'after' => ['status' => 'in_progress'],
        ]);
        $log2->created_at = now();
        $log2->save();

        $result = $this->useCase->execute(1);

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals(2, $result['total']);
        $this->assertEquals('started', $result['data'][0]['action']);
    }
}
