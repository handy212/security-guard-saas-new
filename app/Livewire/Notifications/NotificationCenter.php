<?php

namespace App\Livewire\Notifications;

use App\Models\AuditLog;
use App\Models\OperationalBroadcastDelivery;
use App\Services\NotificationDispatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationCenter extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'all';

    public string $eventType = '';

    public string $severity = '';

    public string $fromDate = '';

    public string $toDate = '';

    public string $policyEvent = '';

    public array $policy = [];

    public function updatedPolicyEvent(): void
    {
        $user = auth()->user();
        abort_unless(NotificationDispatcher::canOperateAlarms($user) && $user->can('settings.manage'), 403);
        $this->policy = [];
        if ($this->policyEvent !== '') {
            $this->validate(['policyEvent' => ['required', Rule::in(array_keys(config('notifications.operational_events')))]]);
            $this->policy = app(NotificationDispatcher::class)->alarmPolicy($user->tenant_id, $this->policyEvent);
        }
    }

    public function savePolicy(NotificationDispatcher $dispatcher): void
    {
        $user = auth()->user();
        abort_unless(NotificationDispatcher::canOperateAlarms($user) && $user->can('settings.manage'), 403);
        $data = $this->validate([
            'policyEvent' => ['required', Rule::in(array_keys(config('notifications.operational_events')))],
            'policy' => ['required', 'array:acknowledgment_seconds,escalation_permission,priority,backup_delay_seconds,backup_permission'],
            'policy.acknowledgment_seconds' => ['required', 'integer', 'min:10', 'max:86400'],
            'policy.escalation_permission' => ['required', Rule::in(NotificationDispatcher::ESCALATION_PERMISSIONS)],
            'policy.backup_delay_seconds' => ['nullable', 'integer', 'between:10,86400'],
            'policy.backup_permission' => ['required_with:policy.backup_delay_seconds', Rule::in(NotificationDispatcher::ESCALATION_PERMISSIONS)],
            'policy.priority' => ['required', 'integer', 'between:0,255'],
        ]);
        $policy = $data['policy'];
        $policy['backup_delay_seconds'] = filled($policy['backup_delay_seconds'] ?? null) ? (int) $policy['backup_delay_seconds'] : null;
        $dispatcher->saveAlarmPolicy($user, $data['policyEvent'], $policy);
        session()->flash('status', 'Alarm policy saved.');
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['filter', 'eventType', 'severity', 'fromDate', 'toDate'], true)) {
            $this->resetPage();
            $this->resetPage('alarmsPage');
        }
    }

    public function read(string $id): void
    {
        auth()->user()->notifications()->whereKey($id)->firstOrFail()->markAsRead();
        $this->dispatch('notification-state-changed');
    }

    public function unread(string $id): void
    {
        auth()->user()->notifications()->whereKey($id)->firstOrFail()->markAsUnread();
        $this->dispatch('notification-state-changed');
    }

    public function archive(string $id): void
    {
        abort_unless(NotificationDispatcher::alarmsEnabled(), 404);
        auth()->user()->notifications()->whereKey($id)->update(['archived_at' => now()]);
        $this->dispatch('notification-state-changed');
    }

    public function restore(string $id): void
    {
        abort_unless(NotificationDispatcher::alarmsEnabled(), 404);
        auth()->user()->notifications()->whereKey($id)->update(['archived_at' => null]);
        $this->dispatch('notification-state-changed');
    }

    public function acknowledge(int $id, NotificationDispatcher $dispatcher): void
    {
        abort_unless(NotificationDispatcher::canOperateAlarms(auth()->user()), 404);
        $dispatcher->acknowledgeAlarm(auth()->user(), $id);
        $this->dispatch('notification-state-changed');
    }

    #[On('notification-stream-changed')]
    public function refreshNotifications(): void {}

    public function render(): View
    {
        $user = auth()->user();
        $canOperate = NotificationDispatcher::canOperateAlarms($user);
        $canConfigure = $canOperate && $user->can('settings.manage');
        $broadcastFailures = 0;
        $deliveryMetrics = null;
        $recentBroadcastFailures = collect();
        if ($canConfigure) {
            $deliveries = OperationalBroadcastDelivery::forTenant($user->tenant_id);
            $this->applyDateFilter($deliveries);
            $deliveryMetrics = $deliveries->selectRaw(
                'count(case when sent_at is null and failed_at is null then 1 end) as pending,
                count(case when sent_at is not null then 1 end) as sent,
                count(case when failed_at is not null then 1 end) as failed,
                coalesce(sum(attempts), 0) as attempts'
            )->first();
            $failures = AuditLog::forTenant($user->tenant_id)->where('action', 'operational_alert.broadcast_failed');
            $this->applyDateFilter($failures);
            $broadcastFailures = (clone $failures)->count();
            if ($broadcastFailures > 0) {
                $recentBroadcastFailures = $failures->latest()->orderByDesc('id')->limit(5)->get(['id', 'created_at']);
            }
        }
        $alarmQuery = app(NotificationDispatcher::class)->alarmsFor($user);
        $alarmQuery
            ->when($this->eventType !== '', fn ($query) => $query->where('event_type', $this->eventType))
            ->when(in_array($this->severity, ['info', 'warning', 'high', 'critical'], true), fn ($query) => $query->where('severity', $this->severity));
        $this->applyDateFilter($alarmQuery);
        $acknowledgmentDuration = DB::getDriverName() === 'sqlite'
            ? "strftime('%s', acknowledged_at) - strftime('%s', created_at)"
            : 'timestampdiff(second, created_at, acknowledged_at)';
        $resolutionDuration = str_replace('acknowledged_at', 'resolved_at', $acknowledgmentDuration);
        $metrics = $canOperate ? (clone $alarmQuery)->selectRaw(
            'count(*) as total,
            count(case when severity = ? then 1 end) as critical,
            count(case when acknowledged_at is null and resolved_at is null then 1 end) as pending,
            count(case when escalated_at is not null or backup_escalated_at is not null then 1 end) as escalated,
            count(case when ((escalation_failed_at is not null and backup_escalated_at is null) or backup_escalation_failed_at is not null) and acknowledged_at is null and resolved_at is null then 1 end) as escalation_failures,
            count(case when resolved_at is not null then 1 end) as resolved,
            count(case when acknowledge_by < coalesce(acknowledged_at, resolved_at, ?) then 1 end) as breaches,
            avg('.$acknowledgmentDuration.') as acknowledgment_seconds,
            avg('.$resolutionDuration.') as resolution_seconds', ['critical', now()]
        )->first() : null;
        $alarms = $alarmQuery;

        match ($this->filter) {
            'critical' => $alarms->where('severity', 'critical'),
            'pending' => $alarms->whereNull('acknowledged_at')->whereNull('resolved_at'),
            'acknowledged' => $alarms->whereNotNull('acknowledged_at'),
            'resolved' => $alarms->whereNotNull('resolved_at'),
            default => null,
        };

        $canArchive = NotificationDispatcher::alarmsEnabled();
        $inbox = $user->notifications()
            ->when($canArchive && $this->filter === 'archived', fn ($query) => $query->whereNotNull('archived_at'))
            ->when($canArchive && $this->filter !== 'archived', fn ($query) => $query->whereNull('archived_at'))
            ->when($this->filter === 'unread', fn ($query) => $query->whereNull('read_at'));
        $this->applyDateFilter($inbox);

        return view('livewire.notifications.notification-center', [
            'showAlarms' => $canOperate,
            'canArchive' => $canArchive,
            'canConfigure' => $canConfigure,
            'broadcastFailures' => $broadcastFailures,
            'deliveryMetrics' => $deliveryMetrics,
            'recentBroadcastFailures' => $recentBroadcastFailures,
            'escalationPermissions' => NotificationDispatcher::ESCALATION_PERMISSIONS,
            'metrics' => $metrics,
            'alarms' => ! $canOperate || in_array($this->filter, ['unread', 'archived'], true) ? null : $alarms->with('acknowledgedBy:id,name')->latest()->latest('id')->paginate(15, pageName: 'alarmsPage'),
            'notifications' => (in_array($this->filter, ['all', 'unread'], true) || ($canArchive && $this->filter === 'archived'))
                ? $inbox->latest()->orderBy('id')->paginate(15)
                : null,
        ])->layout('layouts.app');
    }

    private function applyDateFilter(Builder|Relation $query): void
    {
        if ($this->isValidFilterDate($this->fromDate)) {
            $query->where('created_at', '>=', $this->fromDate.' 00:00:00');
        }
        if ($this->isValidFilterDate($this->toDate)) {
            $query->where('created_at', '<=', $this->toDate.' 23:59:59');
        }
    }

    private function isValidFilterDate(string $date): bool
    {
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $parts)
            && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }
}
