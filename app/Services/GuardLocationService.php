<?php

namespace App\Services;

use App\Models\GuardLocation;
use App\Models\Site;
use App\Models\User;
use App\Models\AttendanceLog;

class GuardLocationService
{
    public function __construct(
        private AttendanceService $attendance,
        private GeofenceAlertService $geofenceAlerts,
    ) {}

    public function record(User $user, float $latitude, float $longitude, ?float $accuracy = null): GuardLocation
    {
        $guardId = $user->guardProfile?->id;
        abort_unless($guardId, 403, 'Guard profile required.');

        $location = GuardLocation::create([
            'tenant_id' => $user->tenant_id,
            'guard_id' => $guardId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_meters' => $accuracy,
            'source' => 'mobile',
            'recorded_at' => now(),
        ]);

        if (config('notifications.geofence_check_on_location')) {
            $this->checkGeofenceForActiveShift($user, $guardId, $latitude, $longitude);
        }

        return $location;
    }

    public function latestForTenant(int $tenantId, int $minutes = 30)
    {
        return GuardLocation::with('assignedGuard')
            ->where('tenant_id', $tenantId)
            ->where('recorded_at', '>=', now()->subMinutes($minutes))
            ->orderByDesc('recorded_at')
            ->get()
            ->unique('guard_id');
    }

    public function historyForGuard(int $guardId, ?string $date = null)
    {
        $date = $date ?? now()->toDateString();

        return GuardLocation::where('guard_id', $guardId)
            ->whereDate('recorded_at', $date)
            ->orderBy('recorded_at')
            ->get();
    }

    public function onDutyGuards(int $tenantId)
    {
        return AttendanceLog::with(['assignedGuard', 'site'])
            ->where('tenant_id', $tenantId)
            ->whereNull('clock_out_at')
            ->latest()
            ->get();
    }

    private function checkGeofenceForActiveShift(User $user, int $guardId, float $lat, float $lng): void
    {
        $log = AttendanceLog::where('guard_id', $guardId)
            ->whereNull('clock_out_at')
            ->latest()
            ->first();

        if (! $log?->site_id) {
            return;
        }

        $site = Site::find($log->site_id);
        if (! $site || $this->attendance->withinGeofence($site, $lat, $lng)) {
            return;
        }

        $this->geofenceAlerts->recordViolation($user->tenant_id, $guardId, $site, $lat, $lng);
    }
}
