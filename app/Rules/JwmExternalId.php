<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class JwmExternalId implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/\A[\x21-\x7e]{1,100}\z/', $value)) {
            $fail('The :attribute must be a string of 1 to 100 printable ASCII characters without whitespace.');
        }
    }
}
