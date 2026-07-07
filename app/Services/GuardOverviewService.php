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
    public function kpiMetrics(Guard $guard, int $tenantId): array
    {
        $stats = $this->stats($guard, $tenantId);

        $licenseStatus = '—';
        $licenseTone = 'default';
        if ($guard->license_number) {
            if ($guard->license_expires_at === null) {
                $licenseStatus = 'Valid (no expiry)';
                $licenseTone = 'success';
            } elseif ($guard->license_expires_at->isFuture()) {
                $licenseStatus = 'Valid until '.$guard->license_expires_at->format('M j, Y');
                $licenseTone = $guard->license_expires_at->diffInDays(now()) <= 30 ? 'warning' : 'success';
            } else {
                $licenseStatus = 'Expired';
                $licenseTone = 'warning';
            }
        } else {
            $licenseStatus = 'Not on file';
            $licenseTone = 'warning';
        }

        $certsExpiring = $guard->certifications()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(30))
            ->count();

        $verificationTone = match ($guard->verification_status) {
            'verified' => 'success',
            'suspended' => 'warning',
            default => 'default',
        };

        return [
            ['label' => 'KYG status', 'value' => ucfirst(str_replace('_', ' ', $guard->verification_status)), 'tone' => $verificationTone],
            ['label' => 'License', 'value' => $licenseStatus, 'tone' => $licenseTone],
            ['label' => 'Certs expiring (30d)', 'value' => $certsExpiring, 'tone' => $certsExpiring > 0 ? 'warning' : 'default'],
            ['label' => 'Shifts (7d)', 'value' => $stats['shifts_completed'].' / '.$stats['shifts_scheduled']],
            ['label' => 'Hours worked (7d)', 'value' => $stats['hours_worked']],
            ['label' => 'Patrols completed (7d)', 'value' => $stats['patrols_completed']],
            ['label' => 'Incidents reported (7d)', 'value' => $stats['incidents_reported'], 'tone' => $stats['incidents_reported'] > 0 ? 'warning' : 'default'],
            ['label' => 'Assigned sites', 'value' => $stats['sites_assigned']],
            ['label' => 'Skills', 'value' => $guard->skills()->count()],
        ];
    }
}
