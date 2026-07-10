<?php

namespace App\Enums;

enum GuardDutyType: string
{
    case Guardian = 'guardian';
    case Dispatch = 'dispatch';

    public function label(): string
    {
        return match ($this) {
            self::Guardian => 'Guardian',
            self::Dispatch => 'Dispatch',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Guardian => 'Site-based / fixed-post security',
            self::Dispatch => 'Mobile response / patrol / control-room deployable',
        };
    }

    public function suspendedMessage(): string
    {
        return match ($this) {
            self::Guardian => 'This Guardian is suspended',
            self::Dispatch => 'This Dispatch officer is suspended',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
