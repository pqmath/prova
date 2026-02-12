<?php

namespace Domain\ValueObjects;

use DateTimeImmutable;

final class ScenarioResult
{
    private bool $success;
    private string $message;
    private int $statusCode;
    private DateTimeImmutable $sentAt;
    private array $responses;

    public function __construct(
        bool $success,
        string $message,
        int $statusCode,
        array $responses = []
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->statusCode = $statusCode;
        $this->sentAt = new DateTimeImmutable();
        $this->responses = $responses;
    }

    public static function success(string $message, int $statusCode = 202, array $responses = []): self
    {
        return new self(true, $message, $statusCode, $responses);
    }

    public static function failure(string $message, int $statusCode = 500, array $responses = []): self
    {
        return new self(false, $message, $statusCode, $responses);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return !$this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getSentAt(): DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function getResponses(): array
    {
        return $this->responses;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'status_code' => $this->statusCode,
            'sent_at' => $this->sentAt->format('Y-m-d H:i:s'),
            'responses' => $this->responses,
        ];
    }
}
