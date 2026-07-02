<?php

namespace App\Services;

use App\Enums\DispatchStatus;
use App\Events\DispatchEventCreated;
use App\Events\DispatchEventUpdated;
use App\Events\SosAlertRaised;
use App\Models\DispatchActivityLog;
use App\Models\DispatchEvent;
use App\Models\Guard;
use App\Models\SosAlert;
use App\Models\User;
use App\Notifications\GenericGuardOpsNotification;
use App\Notifications\SosAlertNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class DispatchService
{
    public function __construct(
        private NotificationDispatcher $notifications,
        private AuditLogService $audit,
        private WebhookDeliveryService $webhooks,
        private FileUploadService $uploads,
    ) {}

    public function createDispatch(array $data, ?UploadedFile $attachment = null): DispatchEvent
    {
        return DB::transaction(function () use ($data, $attachment) {
            $tenantId = (int) $data['tenant_id'];
            $dispatchNumber = $this->nextDispatchNumber($tenantId);

            $event = DispatchEvent::create([
                'tenant_id' => $tenantId,
                'dispatch_number' => $dispatchNumber,
                'client_account_id' => $data['client_account_id'] ?? null,
                'site_id' => $data['site_id'] ?? null,
                'guard_id' => $data['guard_id'] ?? null,
                'created_by_user_id' => $data['created_by_user_id'] ?? null,
                'event_type' => $data['event_type'],
                'priority' => $data['priority'] ?? 'normal',
                'caller_type' => $data['caller_type'] ?? 'client',
                'caller_name' => $data['caller_name'] ?? null,
                'incident_location' => $data['incident_location'] ?? null,
                'incident_date' => $data['incident_date'] ?? now()->toDateString(),
                'incident_time' => $data['incident_time'] ?? now()->format('H:i'),
                'description' => $data['description'] ?? null,
                'action_taken' => $data['action_taken'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'sos_alert_id' => $data['sos_alert_id'] ?? null,
                'status' => isset($data['guard_id']) && $data['guard_id'] ? DispatchStatus::ASSIGNED : DispatchStatus::OPEN,
                'opened_at' => now(),
                'assigned_at' => isset($data['guard_id']) && $data['guard_id'] ? now() : null,
            ]);

            if ($attachment) {
                $event->update([
                    'attachment_path' => $this->uploads->storeDispatchAttachment($tenantId, $event->id, $attachment),
                ]);
            }

            $this->logActivity($event, 'created', 'Dispatch created', $data['created_by_user_id'] ?? null);

            if ($event->guard_id) {
                $this->logActivity($event, 'assigned', 'Guard assigned on creation', $data['created_by_user_id'] ?? null, [
                    'guard_id' => $event->guard_id,
                ]);
                $this->notifyAssignedGuard($event);
            }

            $this->notifyDispatchers($event, 'New dispatch '.$dispatchNumber);

            $eventId = $event->id;
            $payload = $this->payload($event);

            DB::afterCommit(function () use ($event, $tenantId, $payload) {
                DispatchEventCreated::dispatch($event);
                dispatch(function () use ($tenantId, $payload) {
                    app(WebhookDeliveryService::class)->dispatch($tenantId, 'dispatch.created', $payload);
                });
            });

            $this->audit->record('dispatch.created', $event);

            return $event->fresh(['site', 'clientAccount', 'assignedGuard']);
        });
    }

    public function assignGuard(DispatchEvent $event, int $guardId, ?int $userId = null): DispatchEvent
    {
        $event->update([
            'guard_id' => $guardId,
            'status' => DispatchStatus::ASSIGNED,
            'assigned_at' => now(),
        ]);

        $this->logActivity($event, 'assigned', 'Guard assigned', $userId, ['guard_id' => $guardId]);
        $this->notifyAssignedGuard($event);
        $this->broadcastUpdate($event);

        return $event->fresh(['assignedGuard']);
    }

    public function advanceStatus(DispatchEvent $event, ?int $userId = null): DispatchEvent
    {
        $next = $event->status->next();
        abort_unless($next, 422, 'Dispatch cannot be advanced further.');

        $timestamps = match ($next) {
            DispatchStatus::ASSIGNED => ['assigned_at' => now()],
            DispatchStatus::EN_ROUTE => ['en_route_at' => now()],
            DispatchStatus::ON_SCENE => ['on_scene_at' => now()],
            DispatchStatus::RESOLVED => ['resolved_at' => now()],
            DispatchStatus::CLOSED => ['closed_at' => now()],
            default => [],
        };

        $event->update(['status' => $next] + $timestamps);

        $this->logActivity($event, 'status_changed', 'Status → '.$next->label(), $userId, ['status' => $next->value]);
        $this->broadcastUpdate($event);

        return $event->fresh();
    }

    public function setStatus(DispatchEvent $event, DispatchStatus $status, ?int $userId = null, ?string $note = null): DispatchEvent
    {
        $timestamps = match ($status) {
            DispatchStatus::ASSIGNED => ['assigned_at' => $event->assigned_at ?? now()],
            DispatchStatus::EN_ROUTE => ['en_route_at' => $event->en_route_at ?? now()],
            DispatchStatus::ON_SCENE => ['on_scene_at' => $event->on_scene_at ?? now()],
            DispatchStatus::RESOLVED => ['resolved_at' => $event->resolved_at ?? now()],
            DispatchStatus::CLOSED => ['closed_at' => now()],
            DispatchStatus::CANCELLED => ['closed_at' => now()],
            default => [],
        };

        $event->update(['status' => $status] + $timestamps);

        $this->logActivity($event, 'status_changed', $note ?? ('Status → '.$status->label()), $userId, ['status' => $status->value]);
        $this->broadcastUpdate($event);

        return $event->fresh();
    }

    public function updateDispatch(DispatchEvent $event, array $data, ?UploadedFile $attachment = null): DispatchEvent
    {
        $event->update(collect($data)->only([
            'action_taken', 'internal_notes', 'description', 'incident_location',
        ])->filter(fn ($v) => $v !== null)->all());

        if ($attachment) {
            $event->update([
                'attachment_path' => $this->uploads->storeDispatchAttachment($event->tenant_id, $event->id, $attachment),
            ]);
        }

        $this->logActivity($event, 'updated', 'Dispatch details updated', $data['user_id'] ?? null);
        $this->broadcastUpdate($event);

        return $event->fresh();
    }

    public function createFromSos(SosAlert $alert, int $userId): DispatchEvent
    {
        return $this->createDispatch([
            'tenant_id' => $alert->tenant_id,
            'site_id' => $alert->site_id,
            'guard_id' => $alert->guard_id,
            'created_by_user_id' => $userId,
            'event_type' => 'other',
            'priority' => 'critical',
            'caller_type' => 'guard',
            'caller_name' => $alert->assignedGuard?->full_name ?? 'Guard',
            'incident_location' => $alert->site?->name,
            'description' => $alert->message ?? 'SOS alert — immediate response required',
            'latitude' => $alert->latitude,
            'longitude' => $alert->longitude,
            'sos_alert_id' => $alert->id,
        ]);
    }

    /** @deprecated Use createDispatch() */
    public function createEvent(array $data): DispatchEvent
    {
        return $this->createDispatch($data);
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
            '/dispatch',
        );

        $this->audit->record('sos.raised', $alert);
        $this->webhooks->dispatch($user->tenant_id, 'sos.raised', $alert->toArray());

        return $alert;
    }

    public function myActiveDispatches(int $guardId)
    {
        return DispatchEvent::query()
            ->with(['site', 'clientAccount'])
            ->where('guard_id', $guardId)
            ->whereNotIn('status', [DispatchStatus::CLOSED, DispatchStatus::CANCELLED])
            ->latest()
            ->get();
    }

    private function nextDispatchNumber(int $tenantId): string
    {
        $year = now()->year;
        $count = DispatchEvent::where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('DISP-%d-%04d', $year, $count);
    }

    private function logActivity(DispatchEvent $event, string $action, string $message, ?int $userId = null, array $metadata = []): void
    {
        DispatchActivityLog::create([
            'tenant_id' => $event->tenant_id,
            'dispatch_event_id' => $event->id,
            'user_id' => $userId,
            'action' => $action,
            'message' => $message,
            'metadata' => $metadata ?: null,
        ]);
    }

    private function notifyAssignedGuard(DispatchEvent $event): void
    {
        $guard = $event->assignedGuard?->user;
        if (! $guard) {
            return;
        }

        $this->notifications->sendToUser(
            $guard,
            'dispatch.assigned',
            ['number' => $event->dispatch_number, 'site' => $event->site?->name ?? 'Site'],
            new GenericGuardOpsNotification(
                'Dispatch assigned: '.$event->dispatch_number,
                ($event->site?->name ?? 'Site').' — '.$event->event_type,
                '/guard',
                'dispatch.assigned',
            ),
            '/guard',
        );
    }

    private function notifyDispatchers(DispatchEvent $event, string $subject): void
    {
        $this->notifications->sendToTenantAdmins(
            $event->tenant_id,
            'dispatch.created',
            ['number' => $event->dispatch_number, 'priority' => $event->priority->value],
            new GenericGuardOpsNotification(
                $subject,
                ($event->caller_name ?? 'Caller').' — '.($event->incident_location ?? $event->site?->name ?? ''),
                '/dispatch',
                'dispatch.created',
            ),
            '/dispatch',
        );
    }

    private function broadcastUpdate(DispatchEvent $event): void
    {
        $payload = $this->payload($event);

        DB::afterCommit(function () use ($event, $payload) {
            DispatchEventUpdated::dispatch($event);
            dispatch(function () use ($event, $payload) {
                app(WebhookDeliveryService::class)->dispatch($event->tenant_id, 'dispatch.updated', $payload);
            });
        });

        $this->audit->record('dispatch.updated', $event);
    }

    private function payload(DispatchEvent $event): array
    {
        $event->loadMissing(['site', 'clientAccount', 'assignedGuard']);

        return [
            'id' => $event->id,
            'dispatch_number' => $event->dispatch_number,
            'status' => $event->status->value,
            'priority' => $event->priority->value,
            'site' => $event->site?->name,
            'guard' => $event->assignedGuard?->full_name,
        ];
    }
}
