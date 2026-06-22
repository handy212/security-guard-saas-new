<?php

namespace App\Enums;

enum ShiftStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case ASSIGNED = 'assigned';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
