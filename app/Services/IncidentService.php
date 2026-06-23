<?php

namespace App\Services;

use App\Models\Incident;
use App\Notifications\IncidentSubmittedNotification;
use App\Services\AuditLogService;
use App\Services\NotificationDispatcher;
use App\Services\WebhookDeliveryService;

class IncidentService
{
    public function __construct(
        private NotificationDispatcher $notifications,
        private AuditLogService $audit,
        private WebhookDeliveryService $webhooks,
    ) {}

    public function submit(array $data): Incident
    {
        if (isset($data['type']) && ! isset($data['incident_type'])) {
            $data['incident_type'] = $data['type'];
        }

        $incident = Incident::create($data + [
            'status' => 'submitted',
            'reported_at' => now(),
            'occurred_at' => $data['occurred_at'] ?? now(),
        ]);

        $this->notifications->sendToTenantAdmins(
            $incident->tenant_id,
            'incident.submitted',
            ['title' => $incident->title, 'severity' => (string) $incident->severity],
            new IncidentSubmittedNotification($incident),
        );

        $this->audit->record('incident.submitted', $incident, ['severity' => $incident->severity]);
        $this->webhooks->dispatch($incident->tenant_id, 'incident.submitted', $incident->toArray());

        return $incident;
    }

    public function approve(Incident $incident, int $userId): Incident
    {
        $incident->update(['status' => 'approved', 'approved_by_user_id' => $userId, 'approved_at' => now()]);
        return $incident->fresh();
    }

    public function close(Incident $incident, ?string $resolution = null): Incident
    {
        $incident->update(['status' => 'closed', 'resolution' => $resolution, 'closed_at' => now()]);
        return $incident->fresh();
    }
}
