<?php

namespace Application\Factories;

use Domain\Entities\Occurrence;
use Domain\ValueObjects\OccurrenceType;
use DateTimeImmutable;

final class OccurrenceFactory
{
    private const DESCRIPTIONS = [
        'incendio_urbano' => [
            'Incêndio em residência na Rua das Flores',
            'Fogo em estabelecimento comercial',
            'Princípio de incêndio em apartamento',
        ],
        'resgate_veicular' => [
            'Acidente na BR-101 com vítimas presas',
            'Capotamento de veículo na Av. Principal',
            'Colisão entre dois veículos com feridos',
        ],
        'atendimento_pre_hospitalar' => [
            'Vítima de parada cardiorrespiratória',
            'Queda de altura com suspeita de fratura',
            'Mal súbito em via pública',
        ],
        'salvamento_aquatico' => [
            'Pessoa se afogando na Praia Central',
            'Embarcação à deriva',
            'Resgate em área de mangue',
        ],
        'falso_chamado' => [
            'Trote verificado após deslocamento',
            'Ocorrência inexistente no local informado',
        ],
    ];

    public function createRandom(): Occurrence
    {
        $type = OccurrenceType::random();
        $description = $this->getRandomDescription($type);

        return new Occurrence(
            'EXT-' . date('Y') . '-' . str_pad((string) rand(1, 999999), 6, '0', STR_PAD_LEFT),
            $type,
            $description,
            new DateTimeImmutable()
        );
    }

    public function createWithType(string $type): Occurrence
    {
        $occurrenceType = OccurrenceType::from($type);
        $description = $this->getRandomDescription($occurrenceType);

        return new Occurrence(
            'EXT-' . date('Y') . '-' . str_pad((string) rand(1, 999999), 6, '0', STR_PAD_LEFT),
            $occurrenceType,
            $description,
            new DateTimeImmutable()
        );
    }

    public function create(
        string $externalId,
        string $type,
        string $description,
        ?DateTimeImmutable $reportedAt = null
    ): Occurrence {
        return new Occurrence(
            $externalId,
            OccurrenceType::from($type),
            $description,
            $reportedAt ?? new DateTimeImmutable()
        );
    }

    private function getRandomDescription(OccurrenceType $type): string
    {
        $descriptions = self::DESCRIPTIONS[$type->value] ?? ['Ocorrência simulada'];

        return $descriptions[array_rand($descriptions)];
    }
}
