<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case AVAILABLE = 'available';
    case IN_USE = 'in_use';
    case MAINTENANCE = 'maintenance';
    case RETIRED = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::IN_USE => 'In use',
            self::MAINTENANCE => 'Maintenance',
            self::RETIRED => 'Retired',
        };
    }
}
