<?php

namespace App\Enums;

enum PatrolStatus: string
{
    case SCHEDULED = 'scheduled';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case MISSED = 'missed';
    case EXCEPTION = 'exception';
}
