<?php

namespace Tests\Unit\Domain\Factories;

use Domain\Factories\AuditLogFactory;
use Domain\Entities\AuditLog;
use PHPUnit\Framework\TestCase;

class AuditLogFactoryTest extends TestCase
{
    public function test_create_returns_audit_log()
    {
        $factory = new AuditLogFactory();
        $auditLog = $factory->create('entity', 'id', 'action', ['changes']);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('entity', $auditLog->entityType);
        $this->assertEquals('id', $auditLog->entityId);
        $this->assertEquals('action', $auditLog->action);
        $this->assertEquals(['changes'], $auditLog->before);
    }
}
