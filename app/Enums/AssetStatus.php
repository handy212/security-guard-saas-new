<?php

namespace App\Enums;

enum AssetStatus: string
{
    case AVAILABLE = 'available';
    case ISSUED = 'issued';
    case MAINTENANCE = 'maintenance';
    case RETIRED = 'retired';
    case LOST = 'lost';
}
