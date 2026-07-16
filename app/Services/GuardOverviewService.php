<?php

namespace App\Services;

use App\Enums\ShiftAssignmentStatus;
use App\Models\AttendanceLog;
use App\Models\Guard;
use App\Models\Incident;
use App\Models\PatrolSession;
use App\Models\ShiftAssignment;

class GuardOverviewService
{
    /**
     * @return array{
     *     shifts_scheduled: int,
     *     shifts_completed: int,
     *     hours_worked: float,
     *     patrols_completed: int,
     *     incidents_reported: int,
     *     sites_assigned: int,
     * }
     */
    public function stats(Guard $guard, int $tenantId): array
    {
        $since = now()->subDays(7);

        $shiftsScheduled = ShiftAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('guard_id', $guard->id)
            ->where('status', '!=', ShiftAssignmentStatus::CANCELLED->value)
            ->whereHas('shift', fn ($q) => $q->where('starts_at', '>=', $since))
            ->count();

        $shiftsCompleted = ShiftAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('guard_id', $guard->id)
            ->whereHas('shift', fn ($q) => $q->where('ends_at', '>=', $since)->where('ends_at', '<=', now()))
            ->count();

        $minutesWorked = (int) AttendanceLog::query()
            ->where('tenant_id', $tenantId)
            ->where('guard_id', $guard->id)
            ->where('clock_in_at', '>=', $since)
            ->sum('worked_minutes');

        $patrolsCompleted = PatrolSession::query()
            ->where('tenant_id', $tenantId)
            ->where('guard_id', $guard->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $since)
            ->count();

        $assignmentIds = ShiftAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('guard_id', $guard->id)
            ->pluck('id');

        $incidentsReported = Incident::query()
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $since)
            ->whereIn('shift_assignment_id', $assignmentIds)
            ->count();

        return [
            'shifts_scheduled' => $shiftsScheduled,
            'shifts_completed' => $shiftsCompleted,
            'hours_worked' => round($minutesWorked / 60, 1),
            'patrols_completed' => $patrolsCompleted,
            'incidents_reported' => $incidentsReported,
            'sites_assigned' => $guard->siteAssignments()->count(),
        ];
    }

    /**
     * @return array<int, array{label: string, value: string|int|float, tone?: string}>
     */
    public function statusMetrics(Guard $guard): array
    {
        $licenseStatus = 'Not on file';
        $licenseTone = 'warning';
        if ($guard->license_number) {
            if ($guard->license_expires_at === null) {
                $licenseStatus = 'Valid';
                $licenseTone = 'success';
            } elseif ($guard->license_expires_at->isFuture()) {
                $licenseStatus = 'Until '.$guard->license_expires_at->format('M j');
                $licenseTone = $guard->license_expires_at->diffInDays(now()) <= 30 ? 'warning' : 'success';
            } else {
                $licenseStatus = 'Expired';
                $licenseTone = 'warning';
            }
        }

        $certsExpiring = $guard->certifications
            ->filter(fn ($c) => $c->expires_at && $c->expires_at->lte(now()->addDays(30)))
            ->count();

        $verificationTone = match ($guard->verification_status) {
            'verified' => 'success',
            'suspended' => 'warning',
            default => 'default',
        };

        return [
            ['label' => 'KYG', 'value' => ucfirst(str_replace('_', ' ', (string) $guard->verification_status)), 'tone' => $verificationTone],
            ['label' => 'License', 'value' => $licenseStatus, 'tone' => $licenseTone],
            ['label' => 'Certs (30d)', 'value' => $certsExpiring, 'tone' => $certsExpiring > 0 ? 'warning' : 'default'],
            ['label' => 'Skills', 'value' => $guard->skills->count()],
        ];
    }

    /**
     * @return array{shifts: \Illuminate\Support\Collection, incidents: \Illuminate\Support\Collection}
     */
    public function recentActivity(Guard $guard, int $tenantId): array
    {
        $shifts = ShiftAssignment::query()
            ->with(['shift.site'])
            ->where('tenant_id', $tenantId)
            ->where('guard_id', $guard->id)
            ->where('status', '!=', ShiftAssignmentStatus::CANCELLED->value)
            ->whereHas('shift', fn ($q) => $q->where('starts_at', '>=', now()->subDays(14)))
            ->latest('id')
            ->limit(5)
            ->get();

        $assignmentIds = ShiftAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('guard_id', $guard->id)
            ->pluck('id');

        $incidents = Incident::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('shift_assignment_id', $assignmentIds)
            ->latest()
            ->limit(5)
            ->get();

        return [
            'shifts' => $shifts,
            'incidents' => $incidents,
        ];
    }

    /**
     * @return array<int, array{label: string, value: string|int|float, tone?: string}>
     * @deprecated Prefer statusMetrics() + overview stats
     */
    public function kpiMetrics(Guard $guard, int $tenantId): array
    {
        $stats = $this->stats($guard, $tenantId);
        $status = $this->statusMetrics($guard);

        return array_merge($status, [
            ['label' => 'Shifts (7d)', 'value' => $stats['shifts_completed'].' / '.$stats['shifts_scheduled']],
            ['label' => 'Hours worked (7d)', 'value' => $stats['hours_worked']],
            ['label' => 'Patrols completed (7d)', 'value' => $stats['patrols_completed']],
            ['label' => 'Incidents reported (7d)', 'value' => $stats['incidents_reported'], 'tone' => $stats['incidents_reported'] > 0 ? 'warning' : 'default'],
            ['label' => 'Assigned sites', 'value' => $stats['sites_assigned']],
        ]);
    }
}
