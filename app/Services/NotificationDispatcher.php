<?php

namespace App\Services;

use App\Jobs\DeliverOperationalBroadcast;
use App\Models\AuditLog;
use App\Models\NotificationTemplate;
use App\Models\OperationalAlert;
use App\Models\OperationalBroadcastDelivery;
use App\Models\Site;
use App\Models\SosAlert;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use App\Notifications\GenericGuardOpsNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationDispatcher
{
    public const ESCALATION_PERMISSIONS = ['settings.manage', 'audit.view', 'dispatch.manage'];

    public static function alarmsEnabled(): bool
    {
        return (bool) config('notifications.operational_alerts_enabled');
    }

    public function alarmPolicy(int $tenantId, string $eventType): array
    {
        $settings = TenantSetting::forTenant($tenantId)->where('key', 'operational_alarm_policies')->value('value') ?? [];

        return ($settings[$eventType] ?? []) + [
            'acknowledgment_seconds' => max(1, config('notifications.acknowledgment_seconds')),
            'escalation_permission' => 'settings.manage',
            'backup_delay_seconds' => null,
            'backup_permission' => 'dispatch.manage',
            'priority' => config('notifications.operational_events')[$eventType]['priority'],
        ];
    }

    public function saveAlarmPolicy(User $user, string $eventType, array $policy): void
    {
        abort_unless(self::canOperateAlarms($user) && $user->can('settings.manage'), 403);
        DB::transaction(function () use ($user, $eventType, $policy): void {
            Tenant::query()->lockForUpdate()->findOrFail($user->tenant_id);
            $setting = TenantSetting::forTenant($user->tenant_id)->firstOrCreate([
                'tenant_id' => $user->tenant_id,
                'key' => 'operational_alarm_policies',
            ], ['value' => []]);
            $previous = $this->alarmPolicy($user->tenant_id, $eventType);
            $setting->update(['value' => array_replace($setting->value ?? [], [$eventType => $policy])]);

            AuditLog::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'action' => 'operational_alert.policy_updated',
                'auditable_type' => TenantSetting::class,
                'auditable_id' => $setting->id,
                'metadata' => ['event_type' => $eventType, 'previous' => $previous, 'current' => $policy],
            ]);
        }, attempts: 3);
    }

    public function publishAlarm(int $tenantId, string $eventType, string $reference, string $title, string $instructions, string $actionUrl, ?string $severity = null, array $context = []): OperationalAlert
    {
        $definition = config('notifications.operational_events')[$eventType];
        $policy = $this->alarmPolicy($tenantId, $eventType);

        return DB::transaction(function () use ($tenantId, $eventType, $reference, $title, $instructions, $actionUrl, $severity, $context, $definition, $policy): OperationalAlert {
            $deadline = now()->addSeconds((int) $policy['acknowledgment_seconds']);
            $alert = OperationalAlert::forTenant($tenantId)->firstOrCreate([
                'tenant_id' => $tenantId,
                'event_type' => $eventType,
                'source_reference' => $reference,
            ], [
                'title' => $title,
                'instructions' => $instructions,
                'action_url' => $actionUrl,
                'context' => $context,
                'severity' => $severity ?? $definition['severity'],
                'priority' => $policy['priority'],
                'acknowledge_by' => $deadline,
                'backup_escalate_at' => $policy['backup_delay_seconds'] === null
                    ? null : $deadline->copy()->addSeconds((int) $policy['backup_delay_seconds']),
            ]);

            if ($alert->wasRecentlyCreated) {
                $this->recordAlarmAction($alert, 'published');
                $this->signalAlarmChange($tenantId);
            }

            return $alert;
        }, attempts: 3);
    }

    public static function canOperateAlarms(User $user): bool
    {
        return self::alarmsEnabled() && self::canAccessDispatchChannel($user);
    }

    public static function canAccessDispatchChannel(User $user): bool
    {
        return $user->tenant_id !== null && $user->status === 'active' && $user->can('dispatch.manage');
    }

    public function alarmsFor(User $user): Builder
    {
        return OperationalAlert::forTenant($user->tenant_id ?? 0)
            ->when(! self::canOperateAlarms($user), fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when(! $user->can('incidents.manage'), fn (Builder $query) => $query->where('event_type', '!=', 'incident.critical'));
    }

    public function acknowledgeAlarm(User $user, int $id): void
    {
        DB::transaction(function () use ($user, $id): void {
            $alert = $this->alarmsFor($user)->lockForUpdate()->find($id);
            abort_unless($alert, 404);

            if ($alert->acknowledged_at || $alert->resolved_at) {
                return;
            }

            $alert->update(['acknowledged_by' => $user->id, 'acknowledged_at' => now()]);
            if ($alert->event_type === 'sos.raised') {
                SosAlert::forTenant($alert->tenant_id)->whereKey($alert->source_reference)
                    ->where('status', 'open')->update([
                        'status' => 'acknowledged',
                        'acknowledged_by_user_id' => $user->id,
                        'acknowledged_at' => $alert->acknowledged_at,
                    ]);
            }

            $this->recordAlarmAction($alert, 'acknowledged', $user->id);
            $this->signalAlarmChange($alert->tenant_id);
        }, attempts: 3);
    }

    public function resolveAlarm(int $tenantId, string $eventType, string $reference, ?int $actor = null, array $metadata = []): void
    {
        if (! self::alarmsEnabled()) {
            return;
        }

        DB::transaction(function () use ($tenantId, $eventType, $reference, $actor, $metadata): void {
            $alert = OperationalAlert::forTenant($tenantId)->where('event_type', $eventType)
                ->where('source_reference', $reference)->whereNull('resolved_at')->lockForUpdate()->first();

            if ($alert) {
                $alert->update(['resolved_at' => now()]);
                $this->recordAlarmAction($alert, 'resolved', $actor, $metadata);
                $this->signalAlarmChange($tenantId);
            }
        }, attempts: 3);
    }

    public function changeAlarmLink(int $tenantId, string $eventType, string $reference, string $actionUrl): void
    {
        $changed = OperationalAlert::forTenant($tenantId)->where('event_type', $eventType)
            ->where('source_reference', $reference)->whereNull('resolved_at')->where('action_url', '!=', $actionUrl)
            ->update(['action_url' => $actionUrl]);

        if ($changed) {
            $this->signalAlarmChange($tenantId);
        }
    }

    public function escalateDueAlarms(): void
    {
        $this->deliverDueEscalations(backup: false);
        $this->deliverDueEscalations(backup: true);
    }

    private function deliverDueEscalations(bool $backup): void
    {
        $deliveredColumn = $backup ? 'backup_escalated_at' : 'escalated_at';
        $failureColumn = $backup ? 'backup_escalation_failed_at' : 'escalation_failed_at';
        $action = $backup ? 'backup_escalated' : 'escalated';
        $failureAction = $backup ? 'backup_escalation_failed' : 'escalation_failed';
        $permissionKey = $backup ? 'backup_permission' : 'escalation_permission';
        $due = OperationalAlert::query()->withoutGlobalScope('tenant')->whereNull('acknowledged_at')
            ->whereNull('resolved_at')->whereNull('backup_escalated_at')
            ->when(! $backup, fn (Builder $query) => $query->whereNull('escalated_at'))
            ->where($backup ? 'backup_escalate_at' : 'acknowledge_by', '<=', now());

        $due->eachById(function (OperationalAlert $candidate) use ($due, $backup, $deliveredColumn, $failureColumn, $action, $failureAction, $permissionKey): void {
            DB::transaction(function () use ($candidate, $due, $backup, $deliveredColumn, $failureColumn, $action, $failureAction, $permissionKey): void {
                $alert = (clone $due)->lockForUpdate()->find($candidate->id);
                if (! $alert) {
                    return;
                }

                $policy = $this->alarmPolicy($alert->tenant_id, $alert->event_type);
                $supervisors = $this->alarmRecipients($alert->tenant_id, $policy[$permissionKey], $alert->event_type);

                if ($supervisors->isEmpty()) {
                    if (! $alert->{$failureColumn}) {
                        $alert->update([$failureColumn => now()]);
                        $this->recordAlarmAction($alert, $failureAction, metadata: ['reason' => 'no_eligible_recipient']);
                        $this->signalAlarmChange($alert->tenant_id);
                    }

                    return;
                }

                foreach ($supervisors as $supervisor) {
                    $this->storeInboxNotification($supervisor, 'alarm.overdue',
                        $backup ? 'Backup reminder for an unacknowledged alarm' : 'Operational alarm acknowledgment overdue',
                        'Review the outstanding alarms in the Notification Center.',
                        route('notifications.index', ['filter' => 'pending'], absolute: false), 'warning');
                }

                $alert->update([$deliveredColumn => now(), $failureColumn => null]);
                $this->recordAlarmAction($alert, $action, metadata: ['recipient_count' => $supervisors->count()]);
                $this->signalAlarmChange($alert->tenant_id);
            }, attempts: 3);
        });
    }

    public function notifyProtectionRestored(Site $site): void
    {
        foreach ($this->alarmRecipients($site->tenant_id, 'dispatch.manage', 'coverage.restored') as $user) {
            $this->storeInboxNotification($user, 'coverage.restored', 'Site protection restored',
                'Required guard coverage is verified at '.$site->name.'.',
                route('tracking.live', ['site' => $site->id], absolute: false), 'info');
        }

    }

    public function notifyOperators(int $tenantId, string $eventType, string $title, string $body, string $actionUrl, string $permission = 'dispatch.manage'): void
    {
        if (! self::alarmsEnabled()) {
            return;
        }

        $severity = config('notifications.routine_severities')[$eventType] ?? 'info';
        foreach ($this->alarmRecipients($tenantId, $permission, $eventType) as $user) {
            $this->storeInboxNotification($user, $eventType, $title, $body, $actionUrl, $severity);
        }

        $this->signalAlarmChange($tenantId);
    }

    public function observePatriotConnectivity(int $tenantId, bool $available): void
    {
        if (! self::alarmsEnabled()) {
            return;
        }

        DB::transaction(function () use ($tenantId, $available): void {
            Tenant::query()->lockForUpdate()->findOrFail($tenantId);
            $setting = TenantSetting::forTenant($tenantId)->firstOrCreate([
                'tenant_id' => $tenantId,
                'key' => 'patriot_notification_health',
            ], ['value' => ['available' => true]]);

            if ($available === ($setting->value['available'] ?? true)) {
                return;
            }

            $setting->update(['value' => ['available' => $available]]);
            $this->notifyOperators($tenantId, $available ? 'patriot.connection_restored' : 'patriot.connection_unavailable',
                $available ? 'Patriot connection restored' : 'Patriot intake unavailable',
                $available ? 'GuardCore Pro can read the Patriot dispatch queue again.' : 'Check the Patriot connection and review pending requests using the control-room procedure.',
                route('dispatch.control-room', absolute: false));
        }, attempts: 3);
    }

    private function alarmRecipients(int $tenantId, string $permission, string $eventType): Collection
    {
        $previousTeam = getPermissionsTeamId();
        try {
            setPermissionsTeamId($tenantId);

            return User::permission($permission)->where('tenant_id', $tenantId)->where('status', 'active')->get()
                ->filter(fn (User $user) => self::canOperateAlarms($user)
                    && ($eventType !== 'incident.critical' || $user->can('incidents.manage')));
        } finally {
            setPermissionsTeamId($previousTeam);
        }
    }

    private function storeInboxNotification(User $user, string $eventType, string $title, string $body, string $actionUrl, string $severity): void
    {
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => OperationalAlert::class,
            'data' => [
                'title' => $title,
                'body' => $body,
                'action_url' => $actionUrl,
                'event_type' => $eventType,
                'severity' => $severity,
            ],
        ]);
    }

    private function signalAlarmChange(int $tenantId): void
    {
        DB::afterCommit(function () use ($tenantId): void {
            $job = null;
            try {
                $delivery = OperationalBroadcastDelivery::create([
                    'tenant_id' => $tenantId,
                    'connection' => config('broadcasting.default'),
                ]);
                $job = new DeliverOperationalBroadcast($tenantId, $delivery->id);
                Bus::dispatch($job);
            } catch (\Throwable $exception) {
                $job?->failed($exception);
                report($exception);
            }
        });
    }

    private function recordAlarmAction(OperationalAlert $alert, string $action, ?int $actor = null, array $metadata = []): void
    {
        AuditLog::create([
            'tenant_id' => $alert->tenant_id,
            'user_id' => $actor,
            'action' => 'operational_alert.'.$action,
            'auditable_type' => OperationalAlert::class,
            'auditable_id' => $alert->id,
            'metadata' => $metadata,
        ]);
    }

    public function sendToTenantAdmins(int $tenantId, string $templateCode, array $replacements, ?Notification $fallback = null, ?string $actionUrl = '/dashboard'): void
    {
        $users = User::role(['company-admin', 'operations-manager', 'supervisor'])
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->get();

        $this->sendToUsers($users, $tenantId, $templateCode, $replacements, $fallback, $actionUrl);
    }

    public function sendToUser(User $user, string $templateCode, array $replacements, ?Notification $fallback = null, ?string $actionUrl = '/dashboard'): void
    {
        $this->sendToUsers(collect([$user]), $user->tenant_id, $templateCode, $replacements, $fallback, $actionUrl);
    }

    private function sendToUsers(Collection $users, int $tenantId, string $templateCode, array $replacements, ?Notification $fallback, ?string $actionUrl): void
    {
        $template = NotificationTemplate::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $templateCode)
            ->where('is_active', true)
            ->first();

        foreach ($users as $user) {
            if ($template) {
                $user->notify(new GenericGuardOpsNotification(
                    $this->replace($template->subject, $replacements),
                    $this->replace($template->body, $replacements),
                    $actionUrl,
                    $templateCode,
                ));
            } elseif ($fallback) {
                $user->notify($fallback);
            }
        }
    }

    private function replace(?string $text, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $text = str_replace('{{'.$key.'}}', (string) $value, (string) $text);
        }

        return (string) $text;
    }
}
