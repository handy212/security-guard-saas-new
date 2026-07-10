<?php

namespace App\Services;

use App\Enums\DispatchStatus;
use App\Enums\ShiftStatus;
use App\Models\AttendanceLog;
use App\Models\DailyActivityReport;
use App\Models\DispatchEvent;
use App\Models\Guard;
use App\Models\GuardIdleAlert;
use App\Models\Incident;
use App\Models\PatrolSession;
use App\Models\Shift;
use App\Models\Site;
use App\Models\SosAlert;
use App\Support\EnumHelper;
use Illuminate\Support\Collection;

class DashboardMetricsService
{
    public function greeting(): string
    {
        $hour = (int) now()->format('G');

        return match (true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };
    }

    public function kpis(int $tenantId): array
    {
        $activeGuards = Guard::where('tenant_id', $tenantId)->where('status', 'active')->count();
        $onDuty = AttendanceLog::where('tenant_id', $tenantId)
            ->whereNull('clock_out_at')
            ->count();
        $todayShifts = Shift::where('tenant_id', $tenantId)->whereDate('starts_at', today())->count();
        $understaffed = $this->understaffedShifts($tenantId)->count();
        $openIncidents = Incident::where('tenant_id', $tenantId)
            ->whereNotIn('status', ['closed', 'rejected'])
            ->count();
        $openSos = SosAlert::where('tenant_id', $tenantId)->where('status', 'open')->count();
        $patrolRate = $this->patrolCompletionRate($tenantId);
        $sites = Site::where('tenant_id', $tenantId)->where('status', 'active')->count();
        $pendingReports = DailyActivityReport::where('tenant_id', $tenantId)
            ->whereNotIn('status', ['approved', 'rejected'])
            ->count();
        $totalReports = DailyActivityReport::where('tenant_id', $tenantId)->count();
        $idleAlerts = GuardIdleAlert::where('tenant_id', $tenantId)->whereNull('resolved_at')->count();
        $activeTours = PatrolSession::where('tenant_id', $tenantId)
            ->whereDate('started_at', today())
            ->where('status', 'in_progress')
            ->count();
        $completedTours = PatrolSession::where('tenant_id', $tenantId)
            ->whereDate('started_at', today())
            ->where('status', 'completed')
            ->count();
        $openDispatches = DispatchEvent::where('tenant_id', $tenantId)
            ->whereNotIn('status', [DispatchStatus::CLOSED, DispatchStatus::CANCELLED, DispatchStatus::RESOLVED])
            ->count();
        $pendingKyg = Guard::where('tenant_id', $tenantId)
            ->where('verification_status', '!=', 'verified')
            ->where('status', 'active')
            ->count();

        return [
            [
                'key' => 'reports',
                'label' => 'Reports',
                'value' => $totalReports,
                'hint' => $pendingReports ? "Pending {$pendingReports}" : 'All reviewed',
                'tone' => $pendingReports > 0 ? 'warning' : 'default',
                'href' => '/reports/daily',
            ],
            [
                'key' => 'incidents',
                'label' => 'Incidents',
                'value' => $openIncidents,
                'hint' => $openIncidents ? "Open {$openIncidents}" : 'All clear',
                'tone' => $openIncidents > 0 ? 'warning' : 'success',
                'href' => '/incidents?status=open',
            ],
            [
                'key' => 'patrols',
                'label' => 'Tours',
                'value' => $activeTours + $completedTours,
                'hint' => "Completed {$completedTours}",
                'tone' => $patrolRate >= 80 ? 'success' : ($patrolRate >= 50 ? 'warning' : 'danger'),
                'href' => '/patrols',
            ],
            [
                'key' => 'guards',
                'label' => 'On duty',
                'value' => $onDuty,
                'hint' => $onDuty > 0
                    ? "{$onDuty} clocked in · {$activeGuards} on roster"
                    : ($activeGuards ? "{$activeGuards} on roster · none clocked in" : 'No active guards'),
                'tone' => $onDuty > 0 ? 'success' : 'info',
                'href' => '/tracking',
            ],
            [
                'key' => 'shifts',
                'label' => 'Shifts',
                'value' => $todayShifts,
                'hint' => $understaffed
                    ? "{$understaffed} understaffed · {$sites} sites"
                    : $sites.' active sites',
                'tone' => $understaffed > 0 ? 'warning' : 'default',
                'href' => '/schedules?date='.today()->toDateString(),
            ],
            [
                'key' => 'alerts',
                'label' => 'Alerts',
                'value' => $openSos + $idleAlerts + $openDispatches,
                'hint' => collect([
                    $openSos ? "SOS {$openSos}" : null,
                    $openDispatches ? "Dispatch {$openDispatches}" : null,
                    $idleAlerts ? "Idle {$idleAlerts}" : null,
                ])->filter()->implode(' · ') ?: 'None',
                'tone' => ($openSos + $idleAlerts + $openDispatches) > 0 ? 'danger' : 'success',
                'href' => '/dispatch',
            ],
            [
                'key' => 'sos',
                'label' => 'Active SOS',
                'value' => $openSos,
                'hint' => $openSos ? 'Respond immediately' : 'No alerts',
                'tone' => $openSos > 0 ? 'danger' : 'success',
                'href' => '/dispatch',
            ],
            [
                'key' => 'kyg',
                'label' => 'Pending KYG',
                'value' => $pendingKyg,
                'hint' => $pendingKyg ? 'Needs verification' : 'Roster verified',
                'tone' => $pendingKyg > 0 ? 'warning' : 'success',
                'href' => '/guards/know-your-guard',
            ],
        ];
    }

    public function weekTrend(int $tenantId, string $model, int $days = 7): Collection
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $counts = $model::query()
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        return collect(range($days - 1, 0))->mapWithKeys(function (int $offset) use ($counts) {
            $day = now()->subDays($offset)->toDateString();

            return [$day => (int) ($counts[$day] ?? 0)];
        });
    }

    public function todayShifts(int $tenantId, int $limit = 8): Collection
    {
        return Shift::query()
            ->with(['site', 'assignments.assignedGuard'])
            ->where('tenant_id', $tenantId)
            ->whereDate('starts_at', today())
            ->whereNotIn('status', [ShiftStatus::CANCELLED, ShiftStatus::COMPLETED])
            ->orderBy('starts_at')
            ->limit($limit)
            ->get()
            ->map(function (Shift $shift) {
                $staffed = $shift->assignments
                    ->filter(fn ($a) => ! in_array(EnumHelper::value($a->status), ['cancelled', 'no_show'], true))
                    ->count();
                $shift->setAttribute('staffed_count', $staffed);
                $shift->setAttribute('is_understaffed', $staffed < (int) $shift->required_guards);

                return $shift;
            });
    }

    public function understaffedShifts(int $tenantId): Collection
    {
        return $this->todayShifts($tenantId, 50)->filter(fn (Shift $shift) => $shift->is_understaffed)->values();
    }

    public function openDispatches(int $tenantId, int $limit = 5): Collection
    {
        return DispatchEvent::query()
            ->with(['site', 'assignedGuard', 'clientAccount'])
            ->where('tenant_id', $tenantId)
            ->whereNotIn('status', [DispatchStatus::CLOSED, DispatchStatus::CANCELLED, DispatchStatus::RESOLVED])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function attentionItems(int $tenantId): array
    {
        $openSos = SosAlert::where('tenant_id', $tenantId)->where('status', 'open')->count();
        $openDispatches = DispatchEvent::where('tenant_id', $tenantId)
            ->whereNotIn('status', [DispatchStatus::CLOSED, DispatchStatus::CANCELLED, DispatchStatus::RESOLVED])
            ->count();
        $understaffed = $this->understaffedShifts($tenantId)->count();
        $openIncidents = Incident::where('tenant_id', $tenantId)
            ->whereNotIn('status', ['closed', 'rejected'])
            ->whereIn('severity', ['critical', 'high'])
            ->count();
        $idleAlerts = GuardIdleAlert::where('tenant_id', $tenantId)->whereNull('resolved_at')->count();
        $pendingKyg = Guard::where('tenant_id', $tenantId)
            ->where('verification_status', '!=', 'verified')
            ->where('status', 'active')
            ->count();

        return array_values(array_filter([
            $openSos ? [
                'key' => 'sos',
                'label' => $openSos.' open SOS',
                'detail' => 'Immediate response required',
                'href' => route('dispatch.control-room'),
                'tone' => 'danger',
            ] : null,
            $openDispatches ? [
                'key' => 'dispatch',
                'label' => $openDispatches.' active dispatch'.($openDispatches === 1 ? '' : 'es'),
                'detail' => 'Assign or advance response',
                'href' => route('dispatch.control-room'),
                'tone' => 'warning',
            ] : null,
            $understaffed ? [
                'key' => 'staffing',
                'label' => $understaffed.' understaffed shift'.($understaffed === 1 ? '' : 's'),
                'detail' => 'Today\'s roster has gaps',
                'href' => route('schedules.index', ['date' => today()->toDateString()]),
                'tone' => 'warning',
            ] : null,
            $openIncidents ? [
                'key' => 'incidents',
                'label' => $openIncidents.' high-risk incident'.($openIncidents === 1 ? '' : 's'),
                'detail' => 'Review and close or escalate',
                'href' => route('incidents.index', ['status' => 'open', 'severity' => 'high_risk']),
                'tone' => 'danger',
            ] : null,
            $idleAlerts ? [
                'key' => 'idle',
                'label' => $idleAlerts.' idle alert'.($idleAlerts === 1 ? '' : 's'),
                'detail' => 'Check live tracker',
                'href' => route('tracking.live'),
                'tone' => 'warning',
            ] : null,
            $pendingKyg ? [
                'key' => 'kyg',
                'label' => $pendingKyg.' pending KYG',
                'detail' => 'Complete verification before assignment',
                'href' => route('guards.kyg'),
                'tone' => 'info',
            ] : null,
        ]));
    }

    public function recentIncidents(int $tenantId, int $limit = 5): Collection
    {
        return Incident::query()
            ->with('site')
            ->where('tenant_id', $tenantId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function liveAttendance(int $tenantId, int $limit = 8): Collection
    {
        return AttendanceLog::query()
            ->with(['assignedGuard', 'site'])
            ->where('tenant_id', $tenantId)
            ->whereNull('clock_out_at')
            ->latest('clock_in_at')
            ->limit($limit)
            ->get();
    }

    public function patrolCompletionRate(int $tenantId): int
    {
        $total = PatrolSession::where('tenant_id', $tenantId)->whereDate('started_at', today())->count();

        if (! $total) {
            return 100;
        }

        $completed = PatrolSession::where('tenant_id', $tenantId)
            ->whereDate('started_at', today())
            ->where('status', 'completed')
            ->count();

        return (int) round($completed / $total * 100);
    }

    public function weekSummary(int $tenantId): array
    {
        $incidents = Incident::where('tenant_id', $tenantId)->where('created_at', '>=', now()->subDays(7))->count();
        $patrols = PatrolSession::where('tenant_id', $tenantId)->where('created_at', '>=', now()->subDays(7))->count();
        $missedPatrols = PatrolSession::where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays(7))
            ->where('status', 'missed')
            ->count();

        return [
            'incidents' => $incidents,
            'patrols' => $patrols,
            'missed_patrols' => $missedPatrols,
        ];
    }

    public function incidentBreakdown(int $tenantId, int $days = 7): Collection
    {
        return Incident::query()
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw("COALESCE(NULLIF(incident_type, ''), NULLIF(type, ''), 'Other') as category, COUNT(*) as total")
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('total', 'category');
    }

    public function activitySummary(int $tenantId): array
    {
        $from = now()->subDays(6)->startOfDay();

        return [
            'site_tours' => PatrolSession::where('tenant_id', $tenantId)->where('created_at', '>=', $from)->count(),
            'tasks' => \App\Models\TaskSubmission::where('tenant_id', $tenantId)->where('created_at', '>=', $from)->count(),
            'checklists' => \App\Models\CheckpointScan::where('tenant_id', $tenantId)->where('created_at', '>=', $from)->count(),
            'check_ins' => AttendanceLog::where('tenant_id', $tenantId)->where('created_at', '>=', $from)->count(),
            'passdowns' => \App\Models\PassdownLog::where('tenant_id', $tenantId)->where('created_at', '>=', $from)->count(),
            'idle_alerts' => GuardIdleAlert::where('tenant_id', $tenantId)->where('created_at', '>=', $from)->count(),
        ];
    }
}
