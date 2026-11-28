<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReverseGeocodingService
{
    public function address(float $latitude, float $longitude): ?string
    {
        $url = rtrim((string) config('services.geocoding.url'), '/');
        if ($url === '') {
            return null;
        }
        $cacheKey = 'reverse-geocode:'.hash('sha256', sprintf('%s:%.7F:%.7F', $url, $latitude, $longitude));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($url, $latitude, $longitude): ?string {
            try {
                $response = Http::acceptJson()
                    ->withUserAgent((string) config('services.geocoding.user_agent'))
                    ->connectTimeout(3)
                    ->timeout(6)
                    ->withoutRedirecting()
                    ->get($url.'/reverse', [
                        'format' => 'jsonv2',
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'zoom' => 18,
                    ]);

                if (! $response->successful()) {
                    Log::warning('Reverse geocoding request failed.', ['status' => $response->status()]);

                    return null;
                }

                $address = $response->json('display_name');

                return is_string($address) && trim($address) !== '' ? Str::limit(trim($address), 500, '') : null;
            } catch (ConnectionException) {
                Log::warning('Reverse geocoding connection failed.');

                return null;
            }
        });
    }
}
