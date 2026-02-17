<?php

namespace Domain\Enums;

enum IdempotencyStatus: string
{
    case PENDING = 'pending';
    case PROCESSED = 'processed';
    case FAILED = 'failed';
}
