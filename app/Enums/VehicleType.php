<?php

namespace App\Enums;

enum VehicleType: string
{
    case CAR = 'car';
    case MOTOR = 'motor';
    case VAN = 'van';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CAR => 'Car',
            self::MOTOR => 'Motor',
            self::VAN => 'Van',
            self::OTHER => 'Other',
        };
    }
}
