<?php

namespace Domain\Factories;

use Domain\Entities\Dispatch;

class DispatchFactory
{
    public function create(string $occurrenceId, string $resourceCode): Dispatch
    {
        return Dispatch::create($occurrenceId, $resourceCode);
    }
}
