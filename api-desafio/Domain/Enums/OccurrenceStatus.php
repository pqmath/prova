<?php

namespace Domain\Enums;

enum OccurrenceStatus: string
{
    case REPORTED = 'reported';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case CANCELLED = 'cancelled';
}
