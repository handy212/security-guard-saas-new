<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case SCHEDULED = 'scheduled';
    case ON_TIME = 'on_time';
    case LATE = 'late';
    case NO_SHOW = 'no_show';
    case EARLY_LEAVE = 'early_leave';
    case COMPLETED = 'completed';
}
