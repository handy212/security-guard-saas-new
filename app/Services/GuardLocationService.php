<?php

namespace App\Services;

use App\Models\GuardLocation;
use App\Models\User;

class GuardLocationService
{
    public function record(User $user, float $latitude, float $longitude, ?float $accuracy = null): GuardLocation
    {
        $guardId = $user->guardProfile?->id;
        abort_unless($guardId, 403, 'Guard profile required.');

        return GuardLocation::create([
            'tenant_id' => $user->tenant_id,
            'guard_id' => $guardId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_meters' => $accuracy,
            'source' => 'mobile',
            'recorded_at' => now(),
        ]);
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
}
