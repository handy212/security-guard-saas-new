<?php

namespace App\Services;

use App\Models\{DispatchEvent, SosAlert, User};

class DispatchService
{
    public function createEvent(array $data): DispatchEvent
    {
        return DispatchEvent::create($data + ['status' => 'open', 'opened_at' => now()]);
    }

    public function raiseSos(User $user, array $data): SosAlert
    {
        return SosAlert::create([
            'tenant_id' => $user->tenant_id,
            'guard_id' => $user->guardProfile?->id,
            'site_id' => $data['site_id'] ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'message' => $data['message'] ?? 'SOS alert raised',
            'status' => 'open',
            'raised_at' => now(),
        ]);
    }
}
