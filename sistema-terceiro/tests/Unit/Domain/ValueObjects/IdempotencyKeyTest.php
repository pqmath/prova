<?php

namespace tests\Unit\Domain\ValueObjects;

use Domain\ValueObjects\IdempotencyKey;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class IdempotencyKeyTest extends TestCase
{
    public function test_can_generate_key()
    {
        $key = IdempotencyKey::generate();
        $this->assertNotEmpty($key->getValue());
        $this->assertEquals(36, strlen($key->getValue()));
    }

    public function test_can_create_from_string()
    {
        $value = 'test-key-123';
        $key = IdempotencyKey::fromString($value);
        $this->assertEquals($value, $key->getValue());
    }

    public function test_cannot_create_empty_key()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Idempotency key não pode ser vazia');
        new IdempotencyKey('');
    }

    public function test_cannot_create_long_key()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Idempotency key não pode ter mais de 255 caracteres');
        new IdempotencyKey(str_repeat('a', 256));
    }

    public function test_equality()
    {
        $key1 = new IdempotencyKey('abc');
        $key2 = new IdempotencyKey('abc');
        $key3 = new IdempotencyKey('def');

        $this->assertTrue($key1->equals($key2));
        $this->assertFalse($key1->equals($key3));
    }

    public function test_to_string()
    {
        $key = new IdempotencyKey('abc');
        $this->assertEquals('abc', (string) $key);
    }
}
