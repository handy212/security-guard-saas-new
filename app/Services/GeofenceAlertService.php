<?php

namespace App\Services;

use App\Models\GeofenceViolation;
use App\Models\Site;

class GeofenceAlertService
{
    public function __construct(private NotificationDispatcher $notifications) {}

    public function recordViolation(int $tenantId, int $guardId, Site $site, float $lat, float $lng): GeofenceViolation
    {
        $distance = $this->distanceMeters($site->latitude, $site->longitude, $lat, $lng);

        $recent = GeofenceViolation::where('tenant_id', $tenantId)
            ->where('guard_id', $guardId)
            ->where('site_id', $site->id)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->exists();

        if ($recent) {
            return GeofenceViolation::where('tenant_id', $tenantId)
                ->where('guard_id', $guardId)
                ->where('site_id', $site->id)
                ->latest()
                ->first() ?? GeofenceViolation::make([
                    'tenant_id' => $tenantId,
                    'guard_id' => $guardId,
                    'site_id' => $site->id,
                ]);
        }

        $violation = GeofenceViolation::create([
            'tenant_id' => $tenantId,
            'guard_id' => $guardId,
            'site_id' => $site->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'distance_meters' => (int) $distance,
            'notified_at' => now(),
        ]);

        $guard = $violation->assignedGuard;
        $this->notifications->sendToTenantAdmins($tenantId, 'geofence.violation', [
            'guard' => $guard?->full_name ?? 'Guard',
            'site' => $site->name,
            'distance' => (int) $distance,
        ], actionUrl: '/tracking');

        return $violation;
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * atan2(sqrt($a), sqrt(1 - $a));
    }
}
