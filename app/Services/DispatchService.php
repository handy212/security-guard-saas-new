<?php

namespace App\Services;

use App\Events\DispatchEventCreated;
use App\Events\SosAlertRaised;
use App\Models\DispatchEvent;
use App\Models\SosAlert;
use App\Models\User;

class DispatchService
{
    public function createEvent(array $data): DispatchEvent
    {
        $event = DispatchEvent::create($data + ['status' => 'open', 'opened_at' => now()]);
        DispatchEventCreated::dispatch($event);

        return $event;
    }

    public function raiseSos(User $user, array $data): SosAlert
    {
        $alert = SosAlert::create([
            'tenant_id' => $user->tenant_id,
            'guard_id' => $user->guardProfile?->id,
            'site_id' => $data['site_id'] ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'message' => $data['message'] ?? 'SOS alert raised',
            'status' => 'open',
            'raised_at' => now(),
        ]);

        SosAlertRaised::dispatch($alert);

        return $alert;
    }
}
