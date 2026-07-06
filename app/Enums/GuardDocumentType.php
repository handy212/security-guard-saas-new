<?php

namespace App\Enums;

enum GuardDocumentType: string
{
    case ID = 'id';
    case PASSPORT = 'passport';
    case POLICE_CLEARANCE = 'police_clearance';
    case CONTRACT = 'contract';
    case LICENSE = 'license';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ID => 'National ID',
            self::PASSPORT => 'Passport',
            self::POLICE_CLEARANCE => 'Police clearance',
            self::CONTRACT => 'Contract',
            self::LICENSE => 'License',
            self::OTHER => 'Other',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
