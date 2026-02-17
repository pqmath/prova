<?php

namespace Domain\Entities;

use Domain\ValueObjects\OccurrenceType;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class Occurrence
{
    public function __construct(
        public string $externalId,
        public OccurrenceType $type,
        public string $description,
        public DateTimeImmutable $reportedAt
    ) {
        $this->validateExternalId($externalId);
        $this->validateDescription($description);
    }

    private function validateExternalId(string $externalId): void
    {
        if (empty(trim($externalId))) {
            throw new InvalidArgumentException('External ID não pode ser vazio');
        }

        if (strlen($externalId) > 100) {
            throw new InvalidArgumentException('External ID não pode ter mais de 100 caracteres');
        }
    }

    private function validateDescription(string $description): void
    {
        if (empty(trim($description))) {
            throw new InvalidArgumentException('Descrição não pode ser vazia');
        }

        if (strlen($description) > 500) {
            throw new InvalidArgumentException('Descrição não pode ter mais de 500 caracteres');
        }
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getType(): OccurrenceType
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getReportedAt(): DateTimeImmutable
    {
        return $this->reportedAt;
    }

    public function toArray(): array
    {
        return [
            'externalId' => $this->externalId,
            'type' => $this->type->value,
            'description' => $this->description,
            'reportedAt' => $this->reportedAt->format('c'),
        ];
    }

    public function withUpdatedDescription(string $newDescription): self
    {
        return new self(
            $this->externalId,
            $this->type,
            $newDescription,
            $this->reportedAt
        );
    }
}
