<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReverseGeocodingService
{
    public function address(float $latitude, float $longitude): ?string
    {
        $cacheKey = sprintf('reverse-geocode:%0.6f:%0.6f', $latitude, $longitude);

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($latitude, $longitude): ?string {
            try {
                $response = Http::acceptJson()
                    ->withUserAgent((string) config('services.geocoding.user_agent'))
                    ->connectTimeout(3)
                    ->timeout(6)
                    ->retry([200, 500], throw: false)
                    ->get(rtrim((string) config('services.geocoding.url'), '/').'/reverse', [
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

                return is_string($address) && $address !== '' ? $address : null;
            } catch (ConnectionException $exception) {
                Log::warning('Reverse geocoding connection failed.', ['exception' => $exception->getMessage()]);

                return null;
            } catch (Throwable $exception) {
                report($exception);

                return null;
            }
        });
    }
}
