<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\ShiftAssignmentStatus;
use App\Enums\ShiftStatus;
use App\Models\AttendanceLog;
use App\Models\GuardLocation;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\Site;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SiteProtectionService
{
    public const FRESH_GPS_MINUTES = 15;

    public function __construct(
        private AttendanceService $attendance,
        private GuardLocationService $locations,
    ) {}

    /** @return array{sites: Collection, deployments: Collection} */
    public function snapshot(int $tenantId): array
    {
        $now = now();
        $sites = Site::forTenant($tenantId)->with(['clientAccount', 'supervisor'])->orderBy('name')->get()->keyBy('id');
        $shifts = Shift::forTenant($tenantId)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>', $now)
            ->whereNotIn('status', [ShiftStatus::DRAFT, ShiftStatus::CANCELLED, ShiftStatus::COMPLETED])
            ->with(['assignments' => fn ($query) => $query->where('tenant_id', $tenantId)
                ->whereNotIn('status', [ShiftAssignmentStatus::CANCELLED, ShiftAssignmentStatus::NO_SHOW, ShiftAssignmentStatus::COMPLETED])
                ->with('assignedGuard')])
            ->get()
            ->filter(fn (Shift $shift): bool => $sites->has($shift->site_id));

        $assignments = $shifts->pluck('assignments')->collapse();
        $guardIds = $assignments->pluck('guard_id')->unique()->values();
        $openLogs = AttendanceLog::forTenant($tenantId)
            ->where(fn ($query) => $query->whereIn('guard_id', $guardIds)->orWhereIn('shift_assignment_id', $assignments->pluck('id')))
            ->whereNotNull('clock_in_at')->where('clock_in_at', '<=', $now)->whereNull('clock_out_at')
            ->latest('clock_in_at')->latest('id')->get();
        $logsByGuard = $openLogs->groupBy('guard_id');
        $logsByAssignment = $openLogs->groupBy('shift_assignment_id');
        $positions = $this->locations->positionsForGuards($tenantId, $guardIds->all());
        $deploymentsByShift = collect();

        foreach ($shifts as $shift) {
            $site = $sites[$shift->site_id];
            $deploymentsByShift[$shift->id] = $shift->assignments->map(function (ShiftAssignment $assignment) use ($shift, $site, $logsByGuard, $logsByAssignment, $positions, $now): array {
                return $this->deployment($assignment, $shift, $site, $logsByGuard->get($assignment->guard_id, collect()),
                    $logsByAssignment->get($assignment->id, collect()), $positions->get($assignment->guard_id), $now);
            });
        }

        $shiftsBySite = $shifts->groupBy('site_id');
        $coverage = $sites->map(function (Site $site) use ($shiftsBySite, $deploymentsByShift): array {
            $shifts = $shiftsBySite->get($site->id, collect());
            $deployments = $shifts->flatMap(fn (Shift $shift) => $deploymentsByShift[$shift->id]);
            $required = (int) $shifts->sum('required_guards');
            $verified = $deployments->where('verified', true)->unique(fn (array $deployment) => $deployment['guard']?->id)->count();
            $filled = $shifts->sum(fn (Shift $shift): int => min($shift->required_guards, $deploymentsByShift[$shift->id]
                ->where('verified', true)->unique(fn (array $deployment) => $deployment['guard']?->id)->count()));

            $status = match (true) {
                $site->status !== 'active' => 'inactive',
                $required === 0 => 'not_scheduled',
                $deployments->contains('status', 'verification_required') => 'verification_required',
                $filled === 0 => 'unprotected',
                $filled < $required => 'understaffed',
                $deployments->where('verified', true)->contains('geofence', 'unknown') => 'verification_required',
                default => 'protected',
            };

            return compact('site', 'status', 'required', 'verified', 'filled', 'deployments', 'shifts');
        });

        return ['sites' => $coverage, 'deployments' => $deploymentsByShift->collapse()];
    }

    /** @return array<string, mixed> */
    private function deployment(
        ShiftAssignment $assignment,
        Shift $shift,
        Site $site,
        Collection $guardLogs,
        Collection $assignmentLogs,
        ?GuardLocation $location,
        Carbon $now,
    ): array {
        $guard = $assignment->assignedGuard;
        if ($guard?->tenant_id !== $site->tenant_id) {
            $guard = null;
        }

        $checkIn = $assignmentLogs->first(fn (AttendanceLog $log): bool => $log->guard_id === $assignment->guard_id
            && $log->site_id === $site->id);
        $onDuty = $checkIn !== null && $guard?->status === 'active';
        $stale = ! $location || $location->recorded_at->lt($now->copy()->subMinutes(self::FRESH_GPS_MINUTES))
            || ($checkIn && $location->recorded_at->lt($checkIn->clock_in_at));
        $canCheckGeofence = ! $stale && $site->latitude !== null && $site->longitude !== null
            && ($location->accuracy_meters === null || $location->accuracy_meters <= ($site->geofence_radius_meters ?? 150));
        $geofence = match (true) {
            ! $canCheckGeofence => 'unknown',
            $this->attendance->withinGeofence($site, (float) $location->latitude, (float) $location->longitude) => 'inside',
            default => 'outside',
        };
        $ambiguous = ! $guard || $assignmentLogs->count() > 1 || $guardLogs->contains(fn (AttendanceLog $log): bool => $log->shift_assignment_id !== $assignment->id)
            || $assignmentLogs->contains(fn (AttendanceLog $log): bool => $log->guard_id !== $assignment->guard_id || $log->site_id !== $site->id);
        $validCheckIn = $onDuty && in_array($checkIn->status, [AttendanceStatus::ON_TIME, AttendanceStatus::LATE], true)
            && ($checkIn->geofence_validated || $checkIn->is_geofence_valid || $checkIn->reconciled_at !== null);
        $verified = $validCheckIn && ! $ambiguous && $geofence !== 'outside';
        $status = match (true) {
            $ambiguous || ($checkIn && ! $validCheckIn)
                || (! $checkIn && $assignment->status === ShiftAssignmentStatus::IN_PROGRESS) => 'verification_required',
            ! $checkIn => 'awaiting_check_in',
            $geofence === 'outside' => 'outside_geofence',
            default => 'deployed',
        };

        return compact('guard', 'assignment', 'shift', 'site', 'checkIn', 'onDuty', 'location', 'stale', 'geofence', 'verified', 'status');
    }
}
