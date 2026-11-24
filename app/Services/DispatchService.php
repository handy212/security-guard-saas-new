<?php

namespace App\Services;

use App\Enums\DispatchPriority;
use App\Enums\DispatchStatus;
use App\Enums\GuardDutyType;
use App\Events\DispatchEventCreated;
use App\Events\DispatchEventUpdated;
use App\Events\SosAlertRaised;
use App\Models\DispatchActivityLog;
use App\Models\DispatchEvent;
use App\Models\DispatchTeam;
use App\Models\Guard;
use App\Models\Incident;
use App\Models\OperationalAlert;
use App\Models\Site;
use App\Models\SosAlert;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\GenericGuardOpsNotification;
use App\Notifications\SosAlertNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            Tenant::query()->lockForUpdate()->findOrFail($tenantId);
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
                DB::afterCommit(fn () => $this->notifyAssignedGuard($event));
            }

            if (NotificationDispatcher::alarmsEnabled() && $event->priority === DispatchPriority::CRITICAL && ! $event->sos_alert_id) {
                $this->publishDispatchAlarm($event, 'dispatch.critical', 'Critical dispatch');
            } else {
                DB::afterCommit(fn () => $this->notifyDispatchers($event, 'New dispatch '.$dispatchNumber));
            }

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

    public function queuePatriotDecision(DispatchEvent $event, string $decision, int $userId): void
    {
        abort_unless(in_array($decision, ['accept', 'decline'], true), 422);
        DB::transaction(function () use ($event, $decision, $userId): void {
            $event = DispatchEvent::forTenant($event->tenant_id)->lockForUpdate()->findOrFail($event->id);
            abort_unless(Guard::forTenant($event->tenant_id)->whereKey($event->guard_id)->where('user_id', $userId)->exists(), 403);
            if (! $event->patriot_job_id || $event->patriot_response_state !== null || $event->patriot_pending_action !== null
                || $event->status !== DispatchStatus::ASSIGNED || $event->patriot_writeback_error !== null) {
                throw ValidationException::withMessages(['patriotDecision' => 'This Patriot decision is no longer available. Refresh the dispatch.']);
            }

            $event->update(['patriot_pending_action' => $decision] + ($decision === 'accept' ? ['acknowledged_at' => now()] : []));
            $this->logActivity($event, 'patriot_queued', $decision === 'accept'
                ? 'Assigned officer accepted. Reporting to Patriot.'
                : 'Assigned officer declined. Reporting to Patriot.', $userId);
            if ($decision === 'decline') {
                $this->notifications->notifyOperators($event->tenant_id, 'dispatch.guard_declined',
                    'Guard declined dispatch · '.$event->dispatch_number, 'Review the response request and assign an available guard if needed.',
                    route('dispatch.control-room', ['dispatch' => $event->id], absolute: false));
            }
        }, attempts: 3);
    }

    public function assignTeam(DispatchEvent $event, int $teamId, int $userId): void
    {
        DB::transaction(function () use ($event, $teamId, $userId): void {
            $team = DispatchTeam::forTenant($event->tenant_id)->lockForUpdate()->findOrFail($teamId);
            $event = DispatchEvent::forTenant($event->tenant_id)->lockForUpdate()->findOrFail($event->id);
            $members = $team->members()->where('tenant_id', $event->tenant_id)->where('status', 'active')
                ->where('duty_type', GuardDutyType::Dispatch->value)->with('user')->orderBy('id')->get();

            if ($event->status !== DispatchStatus::OPEN || $event->guard_id || $event->dispatch_team_id
                || ! $event->mayAssignFromPatriot() || ($event->patriot_job_id === null && ! $event->site_id && ($event->latitude === null || $event->longitude === null))
                || ! $team->standby_site_id || $members->isEmpty() || $team->outstandingDispatches()->exists()) {
                throw ValidationException::withMessages(['assignTeamId' => 'Select an available team with active officers and a standby site for an open, site-linked dispatch.']);
            }

            $event->update([
                'dispatch_team_id' => $team->id,
                'guard_id' => $members->first()->id,
                'status' => DispatchStatus::ASSIGNED,
                'assigned_at' => now(),
            ]);
            $this->logActivity($event, 'assigned', 'Team assigned: '.$team->name, $userId);
            $this->broadcastUpdate($event);

            DB::afterCommit(function () use ($event, $members): void {
                foreach ($members as $member) {
                    if ($member->user && $member->user->tenant_id === $event->tenant_id) {
                        $this->notifyDispatchRecipient($event, $member->user);
                    }
                }
            });
        }, attempts: 3);
    }

    public function acknowledgeTeamDispatch(DispatchEvent $event, int $userId): void
    {
        DB::transaction(function () use ($event, $userId): void {
            $event = DispatchEvent::forTenant($event->tenant_id)->lockForUpdate()->findOrFail($event->id);
            abort_unless($event->dispatch_team_id && $event->status === DispatchStatus::ASSIGNED, 422);
            if ($event->acknowledged_at === null) {
                $event->update(['acknowledged_at' => now()]);
                $this->logActivity($event, 'acknowledged', 'Dispatch acknowledged by response team', $userId);
                $this->broadcastUpdate($event);
            }
        }, attempts: 3);
    }

    public function recordTeamOutcome(DispatchEvent $event, string $outcome, int $userId): void
    {
        DB::transaction(function () use ($event, $outcome, $userId): void {
            $event = DispatchEvent::forTenant($event->tenant_id)->lockForUpdate()->findOrFail($event->id);
            abort_unless(($event->dispatch_team_id || $event->patriot_job_id) && $event->status === DispatchStatus::ON_SCENE, 422);
            $event->update(['action_taken' => $outcome]);
            $this->advanceStatus($event, $userId);
        }, attempts: 3);
    }

    public function confirmStationReturn(DispatchEvent $event, int $userId): void
    {
        DB::transaction(function () use ($event, $userId): void {
            $event = DispatchEvent::forTenant($event->tenant_id)->lockForUpdate()->findOrFail($event->id);
            if (! $event->awaitingStationReturn()) {
                return;
            }

            $event->update(['returned_at' => now()]);
            $this->logActivity($event, 'returned', 'Team return to standby station confirmed', $userId);
            $this->broadcastUpdate($event);
        }, attempts: 3);
    }

    public function assignGuard(DispatchEvent $event, int $guardId, ?int $userId = null): DispatchEvent
    {
        return DB::transaction(function () use ($event, $guardId, $userId): DispatchEvent {
            $event = DispatchEvent::forTenant($event->tenant_id)->lockForUpdate()->findOrFail($event->id);
            if ($event->guard_id === $guardId && $event->status === DispatchStatus::ASSIGNED) {
                return $event->load('assignedGuard');
            }
            if ($event->dispatch_team_id !== null || ! in_array($event->status, [DispatchStatus::OPEN, DispatchStatus::ASSIGNED], true)
                || ! $event->mayAssignFromPatriot()) {
                throw ValidationException::withMessages(['assignGuardId' => 'Select an open, site-linked dispatch without a pending Patriot update.']);
            }

            $guard = Guard::forTenant($event->tenant_id)->where('status', 'active')->find($guardId);
            if (! $guard || ($event->patriot_job_id && $guard->duty_type !== GuardDutyType::Dispatch)) {
                throw ValidationException::withMessages(['assignGuardId' => 'Select an eligible active guard from this company.']);
            }

            $event->update([
                'guard_id' => $guardId,
                'status' => DispatchStatus::ASSIGNED,
                'assigned_at' => now(),
            ]);

            $this->logActivity($event, 'assigned', 'Guard assigned', $userId, ['guard_id' => $guardId]);
            DB::afterCommit(fn () => $this->notifyAssignedGuard($event));
            $this->broadcastUpdate($event);

            return $event->fresh(['assignedGuard']);
        }, attempts: 3);
    }

    public function advanceStatus(DispatchEvent $event, ?int $userId = null): DispatchEvent
    {
        return DB::transaction(function () use ($event, $userId): DispatchEvent {
            $current = DispatchEvent::forTenant($event->tenant_id)->lockForUpdate()->findOrFail($event->id);
            if ($current->status !== $event->status) {
                return $current;
            }
            $event = $current;
            $next = $event->status->next();
            abort_unless($next, 422, 'Dispatch cannot be advanced further.');

            if ($event->status === DispatchStatus::OPEN && ! $event->guard_id) {
                throw new \RuntimeException('Assign a guard before advancing this dispatch.');
            }

            $timestamps = match ($next) {
                DispatchStatus::ASSIGNED => ['assigned_at' => now()],
                DispatchStatus::EN_ROUTE => ['en_route_at' => now()],
                DispatchStatus::ON_SCENE => ['on_scene_at' => now()],
                DispatchStatus::RESOLVED => ['resolved_at' => now()],
                DispatchStatus::CLOSED => ['closed_at' => now()],
                default => [],
            };

            if ($next === DispatchStatus::EN_ROUTE && $event->dispatch_team_id && ! $event->acknowledged_at) {
                throw ValidationException::withMessages(['patriotDecision' => 'The response team must acknowledge before marking en route.']);
            }
            if ($next === DispatchStatus::RESOLVED && ($event->dispatch_team_id || $event->patriot_job_id)
                && trim((string) $event->action_taken) === '') {
                throw ValidationException::withMessages(['patriotDecision' => 'Record the response outcome before resolving this dispatch.']);
            }

            $patriotAction = null;
            if ($event->patriot_job_id !== null) {
                $requiredState = match ($next) {
                    DispatchStatus::EN_ROUTE, DispatchStatus::ON_SCENE => 'accepted',
                    DispatchStatus::RESOLVED => 'on_site',
                    DispatchStatus::CLOSED => 'completed',
                    default => null,
                };
                if ($requiredState !== null && ($event->patriot_response_state !== $requiredState || $event->patriot_pending_action !== null)) {
                    throw ValidationException::withMessages(['patriotDecision' => 'Wait for Patriot to confirm the previous step before continuing.']);
                }
                $patriotAction = match ($next) {
                    DispatchStatus::ON_SCENE => 'on-site',
                    DispatchStatus::RESOLVED => 'complete',
                    default => null,
                };
            }
            $event->update(['status' => $next] + $timestamps + ($patriotAction ? ['patriot_pending_action' => $patriotAction] : []));

            if ($next === DispatchStatus::RESOLVED && $event->sos_alert_id) {
                SosAlert::forTenant($event->tenant_id)->whereKey($event->sos_alert_id)->update(['status' => 'resolved', 'resolved_at' => now()]);
            }

            $this->logActivity($event, 'status_changed', 'Status → '.$next->label(), $userId, ['status' => $next->value]);
            $this->broadcastUpdate($event);

            return $event->fresh();
        }, attempts: 3);
    }

    public function setStatus(DispatchEvent $event, DispatchStatus $status, ?int $userId = null, ?string $note = null): DispatchEvent
    {
        return DB::transaction(function () use ($event, $status, $userId, $note): DispatchEvent {
            $event = DispatchEvent::forTenant($event->tenant_id)->lockForUpdate()->findOrFail($event->id);
            if ($status === DispatchStatus::CANCELLED && $event->status === DispatchStatus::CANCELLED) {
                return $event;
            }
            if ($status === DispatchStatus::CANCELLED && in_array($event->status, [DispatchStatus::RESOLVED, DispatchStatus::CLOSED], true)) {
                throw ValidationException::withMessages(['patriotDecision' => 'A resolved or closed dispatch cannot be cancelled.']);
            }

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
        }, attempts: 3);
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
        return DB::transaction(function () use ($alert, $userId): DispatchEvent {
            $alert = SosAlert::forTenant($alert->tenant_id)->lockForUpdate()->findOrFail($alert->id);
            $existing = DispatchEvent::forTenant($alert->tenant_id)->where('sos_alert_id', $alert->id)
                ->where('status', '!=', DispatchStatus::CANCELLED)->latest('id')->first();
            if ($existing) {
                return $existing;
            }

            abort_unless(in_array($alert->status, ['open', 'acknowledged'], true), 422);
            $alert->loadMissing(['assignedGuard', 'site']);

            $event = $this->createDispatch([
                'tenant_id' => $alert->tenant_id,
                'client_account_id' => $alert->site?->client_account_id,
                'site_id' => $alert->site_id,
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

            if ($alert->status === 'open' && (! NotificationDispatcher::alarmsEnabled() || ! OperationalAlert::forTenant($alert->tenant_id)
                ->where('event_type', 'sos.raised')->where('source_reference', (string) $alert->id)->exists())) {
                $alert->update(['status' => 'acknowledged', 'acknowledged_by_user_id' => $userId, 'acknowledged_at' => now()]);
            }

            return $event;
        }, attempts: 3);
    }

    public function promoteToIncident(DispatchEvent $event, int $userId): Incident
    {
        return DB::transaction(function () use ($event, $userId): Incident {
            $event = DispatchEvent::forTenant($event->tenant_id)->lockForUpdate()->findOrFail($event->id);
            if ($event->incident_id) {
                return $event->incident()->firstOrFail();
            }

            $priority = is_object($event->priority) ? $event->priority->value : (string) $event->priority;
            $severity = match ($priority) {
                'critical' => 'critical',
                'high' => 'high',
                'low' => 'low',
                default => 'medium',
            };

            $title = trim(($event->dispatch_number ? $event->dispatch_number.' · ' : '').str_replace('_', ' ', (string) $event->event_type));
            if ($event->incident_location) {
                $title .= ' @ '.$event->incident_location;
            }

            $incident = app(IncidentService::class)->submit([
                'tenant_id' => $event->tenant_id,
                'site_id' => $event->site_id,
                'reported_by_user_id' => $userId,
                'title' => $title ?: 'Dispatch incident',
                'type' => $event->event_type ?: 'other',
                'severity' => $severity,
                'description' => $event->description
                    ?: ('Promoted from dispatch '.($event->dispatch_number ?? '#'.$event->id)),
                'latitude' => $event->latitude,
                'longitude' => $event->longitude,
                'occurred_at' => $event->opened_at ?? now(),
            ]);

            $event->update(['incident_id' => $incident->id]);
            $this->logActivity($event, 'promoted', 'Promoted to incident #'.$incident->id, $userId);
            $this->broadcastUpdate($event->fresh());

            return $incident;
        }, attempts: 3);
    }

    /** @deprecated Use createDispatch() */
    public function createEvent(array $data): DispatchEvent
    {
        return $this->createDispatch($data);
    }

    public function raiseSos(User $user, array $data): SosAlert
    {
        $guard = $user->guardProfile;
        abort_unless($user->tenant_id && $guard?->tenant_id === $user->tenant_id, 403);
        abort_if(isset($data['site_id']) && ! Site::forTenant($user->tenant_id)->whereKey($data['site_id'])->exists(), 422, 'The SOS site must belong to your company.');

        $alert = DB::transaction(function () use ($user, $data, $guard): SosAlert {
            $alert = SosAlert::create([
                'tenant_id' => $user->tenant_id,
                'guard_id' => $guard->id,
                'site_id' => $data['site_id'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'message' => $data['message'] ?? 'SOS alert raised',
                'status' => 'open',
                'raised_at' => now(),
            ]);

            if (NotificationDispatcher::alarmsEnabled()) {
                $this->notifications->publishAlarm($user->tenant_id, 'sos.raised', (string) $alert->id, 'Guard SOS',
                    'Review the guard location and coordinate a response.', route('dispatch.control-room', absolute: false).'#dispatch-sos', context: array_filter([
                        'Site' => $alert->site?->name,
                        'Customer' => $alert->site?->clientAccount?->name,
                        'Reference' => 'SOS-'.$alert->id,
                        'Source' => 'GuardCore Pro',
                    ]));
            }

            return $alert;
        }, attempts: 3);

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
            ->with(['site', 'clientAccount', 'dispatchTeam.standbySite'])
            ->where(fn ($query) => $query->where('guard_id', $guardId)
                ->orWhereHas('dispatchTeam.members', fn ($members) => $members->whereKey($guardId)))
            ->where(fn ($query) => $query->whereNotIn('status', [DispatchStatus::CLOSED, DispatchStatus::CANCELLED])
                ->orWhere(fn ($returning) => $returning->whereNotNull('dispatch_team_id')->whereNull('returned_at')))
            ->where(fn ($query) => $query->whereNull('dispatch_team_id')->orWhereNull('returned_at')
                ->orWhereNotIn('status', [DispatchStatus::RESOLVED, DispatchStatus::CLOSED, DispatchStatus::CANCELLED]))
            ->latest()
            ->get();
    }

    private function nextDispatchNumber(int $tenantId): string
    {
        $year = now()->year;
        $prefix = "DISP-{$year}-";
        $sequence = DispatchEvent::forTenant($tenantId)->where('dispatch_number', 'like', $prefix.'%')
            ->selectRaw('MAX(CAST(SUBSTR(dispatch_number, ?) AS UNSIGNED)) AS sequence', [strlen($prefix) + 1])
            ->value('sequence');

        return sprintf('%s%04d', $prefix, ((int) $sequence) + 1);
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

        $this->notifyDispatchRecipient($event, $guard);
    }

    private function notifyDispatchRecipient(DispatchEvent $event, User $user): void
    {
        $this->notifications->sendToUser(
            $user,
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

    public function notifyPatriotIntake(DispatchEvent $event): void
    {
        if (NotificationDispatcher::alarmsEnabled() && $event->priority === DispatchPriority::CRITICAL) {
            $this->publishDispatchAlarm($event, 'patriot.intake', 'Critical Patriot alarm');

            return;
        }

        $template = 'dispatch.created';
        $this->notifications->sendToTenantAdmins(
            $event->tenant_id,
            $template,
            ['number' => $event->dispatch_number, 'priority' => $event->priority->value],
            new GenericGuardOpsNotification('Patriot dispatch '.$event->dispatch_number, 'Review the request in the Dispatch Center.', '/dispatch', $template),
            '/dispatch',
        );
    }

    private function publishDispatchAlarm(DispatchEvent $event, string $eventType, string $title): void
    {
        $this->notifications->publishAlarm($event->tenant_id, $eventType, $this->alarmReference($event),
            $title.' · '.$event->dispatch_number,
            'Review the request and coordinate a response in Dispatch.',
            route('dispatch.control-room', ['dispatch' => $event->id], absolute: false), context: array_filter([
                'Customer' => $event->clientAccount?->name,
                'Site' => $event->site?->name,
                'Location' => $event->incident_location ?? $event->site?->address,
                'Reference' => $event->dispatch_number,
                'Activation' => data_get($event->patriot_intake_snapshot, 'activation_id'),
                'Source' => $event->patriot_job_id ? 'Patriot' : 'GuardCore Pro',
            ]));
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

    public function resolveDispatchAlarm(DispatchEvent $event, ?int $actor = null): void
    {
        if (! NotificationDispatcher::alarmsEnabled()
            || ! in_array($event->status, [DispatchStatus::RESOLVED, DispatchStatus::CLOSED, DispatchStatus::CANCELLED], true)) {
            return;
        }
        if ($event->patriot_job_id && $event->status !== DispatchStatus::CANCELLED && $event->patriot_response_state !== 'completed') {
            return;
        }

        $reference = $this->alarmReference($event);
        $activation = data_get($event->patriot_intake_snapshot, 'activation_id');
        if ($event->patriot_job_id && $activation !== null && $event->status === DispatchStatus::CANCELLED) {
            $remainingDispatch = DispatchEvent::forTenant($event->tenant_id)
                ->where('patriot_instance', $event->patriot_instance)
                ->where('patriot_intake_snapshot->activation_id', $activation)
                ->whereNotIn('status', [DispatchStatus::CANCELLED, DispatchStatus::CLOSED])
                ->whereKeyNot($event->id)->oldest('id')->first();

            if ($remainingDispatch) {
                $this->notifications->changeAlarmLink($event->tenant_id, 'patriot.intake', $reference,
                    route('dispatch.control-room', ['dispatch' => $remainingDispatch->id], absolute: false));

                return;
            }
        }

        $this->notifications->resolveAlarm($event->tenant_id, $event->patriot_job_id ? 'patriot.intake' : 'dispatch.critical',
            $reference, $actor, ['dispatch_status' => $event->status->value]);

        if ($event->sos_alert_id && $event->status !== DispatchStatus::CANCELLED) {
            $this->notifications->resolveAlarm($event->tenant_id, 'sos.raised', (string) $event->sos_alert_id, $actor);
        }
    }

    private function alarmReference(DispatchEvent $event): string
    {
        $activation = data_get($event->patriot_intake_snapshot, 'activation_id');
        if ($event->patriot_job_id && $activation !== null) {
            return 'activation:'.hash('sha256', $event->patriot_instance.':'.$activation);
        }

        return (string) $event->id;
    }

    private function broadcastUpdate(DispatchEvent $event): void
    {
        $this->resolveDispatchAlarm($event, auth()->id());

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
