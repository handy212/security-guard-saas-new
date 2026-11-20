<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

class SafeWebhookUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            $fail('The :attribute must be a valid URL.');

            return;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        $host = parse_url($value, PHP_URL_HOST);
        if (! in_array($scheme, ['http', 'https'], true) || ! is_string($host) || $host === '') {
            $fail('The :attribute must use HTTP or HTTPS.');

            return;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $addresses = [$host];
        } else {
            try {
                $records = dns_get_record($host, DNS_A | DNS_AAAA);
                $addresses = array_values(array_filter(array_map(
                    fn (array $record) => $record['ip'] ?? $record['ipv6'] ?? null,
                    $records
                )));
            } catch (Throwable) {
                $addresses = [];
            }
        }

        if ($addresses === []) {
            $fail('The :attribute host could not be resolved.');

            return;
        }

        foreach ($addresses as $address) {
            if ($this->isPublic($address) === false) {
                $fail('The :attribute cannot target a private or reserved network.');

                return;
            }
        }
    }

    private function isPublic(string $address): bool
    {
        return filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
