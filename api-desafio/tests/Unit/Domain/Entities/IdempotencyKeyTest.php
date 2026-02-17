<?php

namespace Tests\Unit\Domain\Entities;

use Domain\Entities\IdempotencyKey;
use Domain\Enums\IdempotencyStatus;
use PHPUnit\Framework\TestCase;

class IdempotencyKeyTest extends TestCase
{
    public function test_can_create_idempotency_key_with_valid_data()
    {
        $key = 'unique-key-123';
        $source = 'client-app';
        $payload = ['data' => 'content'];

        $idempotency = IdempotencyKey::create($key, $source, $payload);

        $this->assertInstanceOf(IdempotencyKey::class, $idempotency);
        $this->assertNull($idempotency->id);
        $this->assertEquals($key, $idempotency->key);
        $this->assertEquals($source, $idempotency->source);
        $this->assertEquals(IdempotencyStatus::PENDING, $idempotency->status);
        $this->assertEquals($payload, $idempotency->requestPayload);
        $this->assertNull($idempotency->responsePayload);
        $this->assertInstanceOf(\DateTimeImmutable::class, $idempotency->createdAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $idempotency->updatedAt);
    }
}
