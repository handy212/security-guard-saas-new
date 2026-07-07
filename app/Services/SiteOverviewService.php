<?php

namespace App\Services;

use App\Enums\ShiftAssignmentStatus;
use App\Models\AttendanceLog;
use App\Models\Incident;
use App\Models\PatrolSession;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\Site;
use App\Models\TaskSubmission;

class SiteOverviewService
{
    /**
     * @return array{
     *     guards_assigned: int,
     *     tours_completed: int,
     *     incident_reports: int,
     *     tasks_completed: int,
     *     hours_worked: float,
     *     posts: int,
     *     patrol_routes: int,
     * }
     */
    public function stats(Site $site, int $tenantId): array
    {
        $since = now()->subDays(7);

        $guardsAssigned = ShiftAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('status', '!=', ShiftAssignmentStatus::CANCELLED->value)
            ->whereHas('shift', fn ($q) => $q
                ->where('site_id', $site->id)
                ->where('ends_at', '>=', now()))
            ->distinct()
            ->count('guard_id');

        $toursCompleted = PatrolSession::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $since)
            ->whereHas('route', fn ($q) => $q->where('site_id', $site->id))
            ->count();

        $incidentReports = Incident::query()
            ->where('tenant_id', $tenantId)
            ->where('site_id', $site->id)
            ->where('created_at', '>=', $since)
            ->count();

        $tasksCompleted = TaskSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $since)
            ->whereHas('scan.session.route', fn ($q) => $q->where('site_id', $site->id))
            ->count();

        $minutesWorked = (int) AttendanceLog::query()
            ->where('tenant_id', $tenantId)
            ->where('site_id', $site->id)
            ->where('clock_in_at', '>=', $since)
            ->sum('worked_minutes');

        return [
            'guards_assigned' => $guardsAssigned,
            'tours_completed' => $toursCompleted,
            'incident_reports' => $incidentReports,
            'tasks_completed' => $tasksCompleted,
            'hours_worked' => round($minutesWorked / 60, 1),
            'posts' => $site->posts()->count(),
            'patrol_routes' => $site->patrolRoutes()->count(),
        ];
    }

    /**
     * @return array<int, array{lat: float, lng: float, label: string}>
     */
    public function mapMarkers(Site $site): array
    {
        if (! $site->latitude || ! $site->longitude) {
            return [];
        }

        return [[
            'lat' => (float) $site->latitude,
            'lng' => (float) $site->longitude,
            'label' => '<strong>'.e($site->name).'</strong><br>'.e($site->address ?: 'Site location'),
        ]];
    }

    /**
     * @return array{lat: float, lng: float, zoom: int}
     */
    public function mapCenter(Site $site): array
    {
        if ($site->latitude && $site->longitude) {
            return [
                'lat' => (float) $site->latitude,
                'lng' => (float) $site->longitude,
                'zoom' => 15,
            ];
        }

        return ['lat' => 5.6037, 'lng' => -0.1870, 'zoom' => 11];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Shift>
     */
    public function upcomingShifts(Site $site, int $tenantId, int $limit = 10)
    {
        return Shift::query()
            ->with(['assignments.assignedGuard', 'sitePost'])
            ->where('tenant_id', $tenantId)
            ->where('site_id', $site->id)
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')
            ->limit($limit)
            ->get();
    }
}
