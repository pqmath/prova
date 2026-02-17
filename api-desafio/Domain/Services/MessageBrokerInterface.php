<?php

namespace Domain\Services;

interface MessageBrokerInterface
{
    public function publish(string $exchange, string $routingKey, array $message): void;
}
