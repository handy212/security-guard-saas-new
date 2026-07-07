<?php

namespace App\Services;

use App\Enums\ShiftAssignmentStatus;
use App\Models\AttendanceLog;
use App\Models\ClientAccount;
use App\Models\Incident;
use App\Models\PatrolSession;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\Site;
use App\Models\TaskSubmission;

class ClientOverviewService
{
    /**
     * @return array{
     *     sites: int,
     *     guards_assigned: int,
     *     tours_completed: int,
     *     incident_reports: int,
     *     tasks_completed: int,
     *     hours_worked: float,
     * }
     */
    public function stats(ClientAccount $client, int $tenantId): array
    {
        $siteIds = $this->siteIds($client, $tenantId);
        $since = now()->subDays(7);

        $guardsAssigned = ShiftAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('status', '!=', ShiftAssignmentStatus::CANCELLED->value)
            ->whereHas('shift', fn ($q) => $q
                ->where('client_account_id', $client->id)
                ->where('ends_at', '>=', now()))
            ->distinct()
            ->count('guard_id');

        $toursCompleted = PatrolSession::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $since)
            ->whereHas('route.site', fn ($q) => $q->where('client_account_id', $client->id))
            ->count();

        $incidentReports = Incident::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('site_id', $siteIds)
            ->where('created_at', '>=', $since)
            ->count();

        $tasksCompleted = TaskSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $since)
            ->whereHas('scan.session.route.site', fn ($q) => $q->where('client_account_id', $client->id))
            ->count();

        $minutesWorked = (int) AttendanceLog::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('site_id', $siteIds)
            ->where('clock_in_at', '>=', $since)
            ->sum('worked_minutes');

        return [
            'sites' => $siteIds->count(),
            'guards_assigned' => $guardsAssigned,
            'tours_completed' => $toursCompleted,
            'incident_reports' => $incidentReports,
            'tasks_completed' => $tasksCompleted,
            'hours_worked' => round($minutesWorked / 60, 1),
        ];
    }

    /**
     * @return array{
     *     incidents: \Illuminate\Support\Collection,
     *     patrols: \Illuminate\Support\Collection,
     *     shifts: \Illuminate\Support\Collection,
     * }
     */
    public function recentActivity(ClientAccount $client, int $tenantId, int $limit = 5): array
    {
        $siteIds = $this->siteIds($client, $tenantId);

        return [
            'incidents' => Incident::query()
                ->with('site')
                ->where('tenant_id', $tenantId)
                ->whereIn('site_id', $siteIds)
                ->latest()
                ->limit($limit)
                ->get(),
            'patrols' => PatrolSession::query()
                ->with(['route.site', 'assignedGuard'])
                ->where('tenant_id', $tenantId)
                ->whereHas('route.site', fn ($q) => $q->where('client_account_id', $client->id))
                ->latest()
                ->limit($limit)
                ->get(),
            'shifts' => Shift::query()
                ->with(['site', 'assignments.assignedGuard'])
                ->where('tenant_id', $tenantId)
                ->where('client_account_id', $client->id)
                ->latest('starts_at')
                ->limit($limit)
                ->get(),
        ];
    }

    /**
     * @return array<int, array{lat: float, lng: float, label: string}>
     */
    public function mapMarkers(ClientAccount $client, int $tenantId): array
    {
        $markers = [];

        if ($client->latitude && $client->longitude) {
            $markers[] = [
                'lat' => (float) $client->latitude,
                'lng' => (float) $client->longitude,
                'label' => '<strong>'.e($client->name).'</strong><br>Client HQ',
            ];
        }

        foreach ($client->sites as $site) {
            if (! $site->latitude || ! $site->longitude) {
                continue;
            }

            $markers[] = [
                'lat' => (float) $site->latitude,
                'lng' => (float) $site->longitude,
                'label' => '<strong>'.e($site->name).'</strong><br>'.e($site->address ?: 'Post site'),
            ];
        }

        return $markers;
    }

    /**
     * @param  array<int, array{lat: float, lng: float}>  $markers
     * @return array{lat: float, lng: float, zoom: int}
     */
    public function mapCenter(array $markers): array
    {
        if ($markers === []) {
            return ['lat' => 5.6037, 'lng' => -0.1870, 'zoom' => 11];
        }

        $lat = collect($markers)->avg('lat');
        $lng = collect($markers)->avg('lng');

        return [
            'lat' => round((float) $lat, 6),
            'lng' => round((float) $lng, 6),
            'zoom' => count($markers) === 1 ? 14 : (count($markers) <= 3 ? 12 : 10),
        ];
    }

    private function siteIds(ClientAccount $client, int $tenantId)
    {
        return Site::query()
            ->where('tenant_id', $tenantId)
            ->where('client_account_id', $client->id)
            ->pluck('id');
    }
}
