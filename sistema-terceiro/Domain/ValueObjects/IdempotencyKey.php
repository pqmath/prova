<?php

namespace Domain\ValueObjects;

use InvalidArgumentException;
use Illuminate\Support\Str;

final readonly class IdempotencyKey
{
    public function __construct(
        public string $value
    ) {
        if (empty(trim($value))) {
            throw new InvalidArgumentException('Idempotency key não pode ser vazia');
        }

        if (strlen($value) > 255) {
            throw new InvalidArgumentException('Idempotency key não pode ter mais de 255 caracteres');
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public static function generate(): self
    {
        return new self(Str::uuid()->toString());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
