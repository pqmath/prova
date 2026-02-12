<?php

namespace Domain\Entities;

use Domain\ValueObjects\OccurrenceType;
use DateTimeImmutable;
use InvalidArgumentException;

final class Occurrence
{
    private string $externalId;
    private OccurrenceType $type;
    private string $description;
    private DateTimeImmutable $reportedAt;

    public function __construct(
        string $externalId,
        OccurrenceType $type,
        string $description,
        DateTimeImmutable $reportedAt
    ) {
        $this->setExternalId($externalId);
        $this->type = $type;
        $this->setDescription($description);
        $this->reportedAt = $reportedAt;
    }

    private function setExternalId(string $externalId): void
    {
        if (empty(trim($externalId))) {
            throw new InvalidArgumentException('External ID não pode ser vazio');
        }

        if (strlen($externalId) > 100) {
            throw new InvalidArgumentException('External ID não pode ter mais de 100 caracteres');
        }

        $this->externalId = $externalId;
    }

    private function setDescription(string $description): void
    {
        if (empty(trim($description))) {
            throw new InvalidArgumentException('Descrição não pode ser vazia');
        }

        if (strlen($description) > 500) {
            throw new InvalidArgumentException('Descrição não pode ter mais de 500 caracteres');
        }

        $this->description = $description;
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
            'type' => $this->type->getValue(),
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
