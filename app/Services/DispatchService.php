<?php

namespace App\Services;

use App\Events\DispatchEventCreated;
use App\Events\SosAlertRaised;
use App\Models\DispatchEvent;
use App\Models\SosAlert;
use App\Models\User;
use App\Notifications\SosAlertNotification;

class DispatchService
{
    public function __construct(
        private NotificationDispatcher $notifications,
        private AuditLogService $audit,
        private WebhookDeliveryService $webhooks,
    ) {}

    public function createEvent(array $data): DispatchEvent
    {
        $event = DispatchEvent::create($data + ['status' => 'open', 'opened_at' => now()]);
        DispatchEventCreated::dispatch($event);
        $this->audit->record('dispatch.event.created', $event);
        $this->webhooks->dispatch($event->tenant_id, 'dispatch.event.created', $event->toArray());

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

        $this->notifications->sendToTenantAdmins(
            $user->tenant_id,
            'sos.raised',
            ['message' => $alert->message ?? 'SOS alert'],
            new SosAlertNotification($alert),
        );

        $this->audit->record('sos.raised', $alert);
        $this->webhooks->dispatch($user->tenant_id, 'sos.raised', $alert->toArray());

        return $alert;
    }
}
