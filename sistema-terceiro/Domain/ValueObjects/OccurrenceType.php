<?php

namespace Domain\ValueObjects;

enum OccurrenceType: string
{
    case IncendioUrbano = 'incendio_urbano';
    case ResgateVeicular = 'resgate_veicular';
    case AtendimentoPreHospitalar = 'atendimento_pre_hospitalar';
    case SalvamentoAquatico = 'salvamento_aquatico';
    case FalsoChamado = 'falso_chamado';

    public function getValue(): string
    {
        return $this->value;
    }

    public static function random(): self
    {
        $cases = self::cases();
        return $cases[array_rand($cases)];
    }

    public static function all(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }
}
