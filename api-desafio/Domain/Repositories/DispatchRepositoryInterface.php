<?php

namespace Domain\Repositories;

use Domain\Entities\Dispatch;

interface DispatchRepositoryInterface
{
    public function save(Dispatch $dispatch): void;
}
