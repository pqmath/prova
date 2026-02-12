<?php

namespace Domain\DTOs;

final class ApiResponse
{
    public function __construct(
        private readonly int $statusCode,
        private readonly array $body = [],
        private readonly array $headers = []
    ) {
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function isAccepted(): bool
    {
        return $this->statusCode === 202;
    }

    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    public function toArray(): array
    {
        return [
            'status_code' => $this->statusCode,
            'body' => $this->body,
            'headers' => $this->headers,
        ];
    }
}
