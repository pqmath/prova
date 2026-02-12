<?php

namespace Domain\ValueObjects;

use InvalidArgumentException;

final class OccurrenceType
{
    public const INCENDIO_URBANO = 'incendio_urbano';
    public const RESGATE_VEICULAR = 'resgate_veicular';
    public const APH = 'atendimento_pre_hospitalar';
    public const SALVAMENTO_AQUATICO = 'salvamento_aquatico';
    public const FALSO_CHAMADO = 'falso_chamado';

    private const VALID_TYPES = [
        self::INCENDIO_URBANO,
        self::RESGATE_VEICULAR,
        self::APH,
        self::SALVAMENTO_AQUATICO,
        self::FALSO_CHAMADO,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (!in_array($value, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Tipo de ocorrência inválido: %s. Tipos válidos: %s',
                    $value,
                    implode(', ', self::VALID_TYPES)
                )
            );
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public static function random(): self
    {
        $randomType = self::VALID_TYPES[array_rand(self::VALID_TYPES)];
        return new self($randomType);
    }

    public static function all(): array
    {
        return self::VALID_TYPES;
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
