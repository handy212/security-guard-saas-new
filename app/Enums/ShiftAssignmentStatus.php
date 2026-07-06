<?php

namespace App\Enums;

enum ShiftAssignmentStatus: string
{
    case ASSIGNED = 'assigned';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case NO_SHOW = 'no_show';
    case CANCELLED = 'cancelled';
}
