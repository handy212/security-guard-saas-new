<?php

namespace App\Services;

use App\Models\Guard;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftConfirmation;
use App\Services\LeaveSchedulingService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Enums\ShiftStatus;
use App\Support\EnumHelper;
use RuntimeException;

class ScheduleService
{
    public function createShift(array $data): Shift
    {
        return Shift::create([
            'tenant_id' => $data['tenant_id'],
            'client_account_id' => $data['client_account_id'],
            'site_id' => $data['site_id'],
            'site_post_id' => $data['site_post_id'] ?? null,
            'title' => $data['title'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'required_guards' => $data['required_guards'] ?? 1,
            'billing_rate' => $data['billing_rate'] ?? 0,
            'status' => $data['status'] ?? 'open',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function updateShift(Shift $shift, array $data): Shift
    {
        $shift->update([
            'client_account_id' => $data['client_account_id'],
            'site_id' => $data['site_id'],
            'site_post_id' => filled($data['site_post_id'] ?? null) ? $data['site_post_id'] : null,
            'title' => $data['title'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'required_guards' => $data['required_guards'] ?? 1,
            'billing_rate' => $data['billing_rate'] ?? 0,
            'status' => $data['status'] ?? $shift->status,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->refreshShiftStaffingStatus($shift);

        return $shift->fresh();
    }

    public function cancelShift(Shift $shift): void
    {
        $shift->assignments()
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->each(fn (ShiftAssignment $assignment) => $this->unassignGuard($assignment));

        $shift->update(['status' => ShiftStatus::CANCELLED]);
    }

    public function assignGuard(Shift $shift, Guard $guard): ShiftAssignment
    {
        if ($guard->verification_status !== 'verified') {
            throw new RuntimeException('Guard must be verified before assignment. Complete Know Your Guard vetting first.');
        }

        if (app(LeaveSchedulingService::class)->hasApprovedLeaveConflict($guard, $shift->starts_at, $shift->ends_at)) {
            throw new RuntimeException('Guard has approved leave during this shift. Reschedule the shift or update the leave request first.');
        }

        if ($this->hasConflict($guard, $shift->starts_at, $shift->ends_at, $shift->id)) {
            throw new RuntimeException('Guard has another assignment in this time range.');
        }

        if ($shift->assignments()->whereNotIn('status', ['cancelled', 'completed'])->count() >= $shift->required_guards) {
            throw new RuntimeException('This shift already has the required number of guards.');
        }

        $assignment = ShiftAssignment::create([
            'tenant_id' => $shift->tenant_id,
            'shift_id' => $shift->id,
            'guard_id' => $guard->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        app(WorkforceService::class)->requestConfirmation($assignment);
        $this->refreshShiftStaffingStatus($shift);

        return $assignment;
    }

    public function unassignGuard(ShiftAssignment $assignment): void
    {
        $shift = $assignment->shift;

        $assignment->update(['status' => 'cancelled']);
        ShiftConfirmation::where('shift_assignment_id', $assignment->id)->delete();

        $this->refreshShiftStaffingStatus($shift);
    }

    public function refreshShiftStaffingStatus(Shift $shift): void
    {
        $shift->refresh();
        $activeAssignments = $shift->assignments()->whereNotIn('status', ['cancelled', 'completed'])->count();

        if (EnumHelper::is($shift->status, 'cancelled')) {
            return;
        }

        if ($activeAssignments >= $shift->required_guards) {
            $shift->update(['status' => 'assigned']);
        } elseif ($activeAssignments > 0) {
            $shift->update(['status' => 'open']);
        } else {
            $shift->update(['status' => 'open']);
        }
    }

    public function hasConflict(Guard $guard, string|Carbon $start, string|Carbon $end, ?int $excludeShiftId = null): bool
    {
        return ShiftAssignment::where('guard_id', $guard->id)
            ->whereNotIn('status', ['cancelled', 'no_show', 'completed'])
            ->whereHas('shift', function ($query) use ($start, $end, $excludeShiftId) {
                $query->where('starts_at', '<', Carbon::parse($end))
                    ->where('ends_at', '>', Carbon::parse($start))
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->when($excludeShiftId, fn ($q) => $q->where('id', '!=', $excludeShiftId));
            })->exists();
    }

    public function openShiftsForDate(string $date): Collection
    {
        return Shift::with(['site', 'sitePost', 'assignments.assignedGuard'])
            ->whereDate('starts_at', $date)
            ->orderBy('starts_at')
            ->get();
    }
}
