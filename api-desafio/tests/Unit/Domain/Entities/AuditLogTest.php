<?php

namespace Tests\Unit\Domain\Entities;

use DateTimeImmutable;
use Domain\Entities\AuditLog;
use PHPUnit\Framework\TestCase;

class AuditLogTest extends TestCase
{
    public function test_can_instantiate_audit_log_via_constructor()
    {
        $now = new DateTimeImmutable();
        $auditLog = new AuditLog(
            1,
            'occurrence',
            'uuid-123',
            'create',
            ['old' => 'value'],
            ['new' => 'value'],
            ['ip' => '127.0.0.1'],
            $now
        );

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals(1, $auditLog->id);
        $this->assertEquals('occurrence', $auditLog->entityType);
        $this->assertEquals('uuid-123', $auditLog->entityId);
        $this->assertEquals('create', $auditLog->action);
        $this->assertEquals(['old' => 'value'], $auditLog->before);
        $this->assertEquals(['new' => 'value'], $auditLog->after);
        $this->assertEquals(['ip' => '127.0.0.1'], $auditLog->meta);
        $this->assertSame($now, $auditLog->createdAt);
    }

    public function test_can_create_audit_log_via_static_method()
    {
        $auditLog = AuditLog::create(
            'occurrence',
            'uuid-456',
            'update',
            ['status' => 'pending'],
            ['status' => 'in_progress']
        );

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertNull($auditLog->id);
        $this->assertEquals('occurrence', $auditLog->entityType);
        $this->assertEquals('uuid-456', $auditLog->entityId);
        $this->assertEquals('update', $auditLog->action);
        $this->assertEquals(['status' => 'pending'], $auditLog->before);
        $this->assertEquals(['status' => 'in_progress'], $auditLog->after);
        $this->assertNull($auditLog->meta);
        $this->assertInstanceOf(DateTimeImmutable::class, $auditLog->createdAt);
    }
}
