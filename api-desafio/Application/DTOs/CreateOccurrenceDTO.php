<?php

namespace Application\DTOs;

readonly class CreateOccurrenceDTO
{
    public function __construct(
        public string $externalId,
        public string $type,
        public string $description,
        public string $reportedAt,
    ) {
    }
}
