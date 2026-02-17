<?php

namespace Tests\Unit\Domain\Factories;

use Domain\Factories\IdempotencyKeyFactory;
use Domain\Entities\IdempotencyKey;
use PHPUnit\Framework\TestCase;

class IdempotencyKeyFactoryTest extends TestCase
{
    public function test_create_returns_idempotency_key()
    {
        $factory = new IdempotencyKeyFactory();
        $key = $factory->create('key', 'source', ['payload']);

        $this->assertInstanceOf(IdempotencyKey::class, $key);
        $this->assertEquals('key', $key->key);
        $this->assertEquals('source', $key->source);
        $this->assertEquals(['payload'], $key->requestPayload);
    }
}
