<?php

namespace App\Services;

use App\Models\Incident;
use App\Notifications\IncidentSubmittedNotification;
use App\Services\AuditLogService;
use App\Services\NotificationDispatcher;
use App\Services\WebhookDeliveryService;
use App\Support\MutableStatus;

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

    public function update(Incident $incident, array $data): Incident
    {
        MutableStatus::assertMutable($incident);

        if (isset($data['type']) && ! isset($data['incident_type'])) {
            $data['incident_type'] = $data['type'];
        }

        $incident->update(collect($data)->only([
            'site_id', 'title', 'type', 'incident_type', 'severity', 'description',
            'latitude', 'longitude', 'occurred_at',
        ])->filter(fn ($v) => $v !== null)->all());

        $this->audit->record('incident.updated', $incident);

        return $incident->fresh();
    }

    public function delete(Incident $incident): void
    {
        MutableStatus::assertMutable($incident);
        $this->audit->record('incident.deleted', $incident, ['title' => $incident->title]);
        $incident->media()->delete();
        $incident->delete();
    }

    public function approve(Incident $incident, int $userId): Incident
    {
        $incident->update(['status' => 'approved', 'approved_by_user_id' => $userId, 'approved_at' => now()]);
        return $incident->fresh();
    }

    public function reject(Incident $incident, ?string $resolution = null): Incident
    {
        $incident->update([
            'status' => 'rejected',
            'resolution' => $resolution,
            'closed_at' => now(),
        ]);

        return $incident->fresh();
    }

    public function close(Incident $incident, ?string $resolution = null): Incident
    {
        $incident->update(['status' => 'closed', 'resolution' => $resolution, 'closed_at' => now()]);
        return $incident->fresh();
    }
}
