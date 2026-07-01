<?php

namespace Tests\Feature;

use App\Services\TwoFactorService;
use Tests\TestCase;

class TwoFactorServiceTest extends TestCase
{
    public function test_verify_code_accepts_current_totp(): void
    {
        $service = app(TwoFactorService::class);
        $secret = $service->generateSecret();

        $timestamp = time();
        $counter = pack('N*', 0, intdiv($timestamp, 30));
        $hash = hash_hmac('sha1', $counter, $this->base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );
        $code = str_pad((string) ($binary % 1_000_000), 6, '0', STR_PAD_LEFT);

        $this->assertTrue($service->verifyCode($secret, $code));
    }

    public function test_verify_code_rejects_invalid_code(): void
    {
        $service = app(TwoFactorService::class);

        $this->assertFalse($service->verifyCode($service->generateSecret(), '000000'));
    }

    private function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        $bits = '';
        $output = '';

        foreach (str_split($secret) as $char) {
            $value = strpos($alphabet, $char);
            if ($value === false) {
                continue;
            }
            $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }

        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $output .= chr(bindec($chunk));
            }
        }

        return $output;
    }
}
