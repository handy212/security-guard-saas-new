<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\GuardLocation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Collection;

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

    /** @param list<int> $guardIds */
    public function positionsForGuards(int $tenantId, array $guardIds): Collection
    {
        if ($guardIds === []) {
            return collect();
        }

        $latestIds = GuardLocation::forTenant($tenantId)->whereIn('guard_id', $guardIds)->where('recorded_at', '<=', now())
            ->select('id')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY guard_id ORDER BY recorded_at DESC, id DESC) AS position_rank');

        return GuardLocation::forTenant($tenantId)
            ->whereIn('id', fn ($query) => $query->fromSub($latestIds, 'positions')->select('id')->where('position_rank', 1))
            ->get()->keyBy('guard_id');
    }

    public function historyForGuard(int $guardId, ?string $date = null, ?int $tenantId = null)
    {
        $date = $date ?? now()->toDateString();

        return GuardLocation::where('guard_id', $guardId)
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
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
