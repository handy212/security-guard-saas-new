<?php

namespace App\Support;

use BackedEnum;

class EnumHelper
{
    public static function value(mixed $status): string
    {
        if ($status instanceof BackedEnum) {
            return $status->value;
        }

        return (string) $status;
    }

    /** @param  list<string>  $values */
    public static function is(mixed $status, string $value): bool
    {
        if ($status instanceof BackedEnum) {
            return $status->value === $value;
        }

        return (string) $status === $value;
    }

    /** @param  list<string>  $values */
    public static function isOneOf(mixed $status, array $values): bool
    {
        $current = $status instanceof BackedEnum ? $status->value : (string) $status;

        return in_array($current, $values, true);
    }

    /** @param  list<string>  $values */
    public static function isNotOneOf(mixed $status, array $values): bool
    {
        return ! self::isOneOf($status, $values);
    }
}
