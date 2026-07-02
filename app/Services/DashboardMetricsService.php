<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\DailyActivityReport;
use App\Models\Guard;
use App\Models\GuardIdleAlert;
use App\Models\Incident;
use App\Models\PatrolSession;
use App\Models\Shift;
use App\Models\Site;
use App\Models\SosAlert;
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
            ->whereDate('clock_in_at', today())
            ->count();
        $todayShifts = Shift::where('tenant_id', $tenantId)->whereDate('starts_at', today())->count();
        $openIncidents = Incident::where('tenant_id', $tenantId)
            ->whereNotIn('status', ['closed', 'rejected'])
            ->count();
        $openSos = SosAlert::where('tenant_id', $tenantId)->where('status', 'open')->count();
        $patrolRate = $this->patrolCompletionRate($tenantId);
        $verifiedGuards = Guard::where('tenant_id', $tenantId)->where('verification_status', 'verified')->count();
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
                'href' => '/incidents',
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
                'hint' => "{$activeGuards} active guards",
                'tone' => 'info',
                'href' => '/guards',
            ],
            [
                'key' => 'shifts',
                'label' => 'Shifts',
                'value' => $todayShifts,
                'hint' => $sites.' active sites',
                'tone' => 'default',
                'href' => '/schedules',
            ],
            [
                'key' => 'alerts',
                'label' => 'Alerts',
                'value' => $openSos + $idleAlerts,
                'hint' => $openSos ? "SOS {$openSos}" : ($idleAlerts ? "Idle {$idleAlerts}" : 'None'),
                'tone' => ($openSos + $idleAlerts) > 0 ? 'danger' : 'success',
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

    public function todayShifts(int $tenantId, int $limit = 6): Collection
    {
        return Shift::query()
            ->with(['site', 'assignments.assignedGuard'])
            ->where('tenant_id', $tenantId)
            ->whereDate('starts_at', today())
            ->orderBy('starts_at')
            ->limit($limit)
            ->get();
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

    public function liveAttendance(int $tenantId, int $limit = 5): Collection
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
