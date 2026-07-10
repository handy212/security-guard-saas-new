<?php

namespace App\Services;

use App\Enums\ShiftStatus;
use App\Models\Guard;
use App\Models\GuardAvailability;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftConfirmation;
use App\Support\EnumHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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

        if (! $this->isAvailableForShift($guard, $shift)) {
            throw new RuntimeException('Guard is not available for this shift window. Update weekly availability or pick another guard.');
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

    /**
     * No availability rows = fully available.
     * Otherwise the shift must sit inside an available window for each weekday it spans.
     */
    public function isAvailableForShift(Guard $guard, Shift $shift): bool
    {
        $slots = GuardAvailability::query()
            ->where('guard_id', $guard->id)
            ->get();

        if ($slots->isEmpty()) {
            return true;
        }

        $startsAt = Carbon::parse($shift->starts_at);
        $endsAt = Carbon::parse($shift->ends_at);
        $cursor = $startsAt->copy();

        while ($cursor->lt($endsAt)) {
            $dayBoundary = $cursor->copy()->endOfDay();
            $segmentEnd = $endsAt->lt($dayBoundary) ? $endsAt->copy() : $dayBoundary;
            $weekday = $cursor->dayOfWeek;
            $needStart = $cursor->format('H:i');
            $needEnd = $endsAt->isSameDay($cursor)
                ? $endsAt->format('H:i')
                : '23:59';

            $daySlots = $slots->where('weekday', $weekday);
            if ($daySlots->isEmpty()) {
                return false;
            }

            $covered = $daySlots->contains(function (GuardAvailability $slot) use ($needStart, $needEnd) {
                if (! $slot->is_available) {
                    return false;
                }

                $slotStart = substr((string) $slot->starts_at, 0, 5);
                $slotEnd = substr((string) $slot->ends_at, 0, 5);

                return $slotStart <= $needStart && $slotEnd >= $needEnd;
            });

            if (! $covered) {
                return false;
            }

            $cursor = $cursor->copy()->addDay()->startOfDay();
            if ($cursor->gte($endsAt)) {
                break;
            }
        }

        return true;
    }

    public function refreshShiftStaffingStatus(Shift $shift): void
    {
        $shift->refresh();

        if (EnumHelper::is($shift->status, 'cancelled')) {
            return;
        }

        $assignments = $shift->assignments()->where('status', '!=', 'cancelled')->get();
        $inProgress = $assignments->filter(fn ($a) => EnumHelper::is($a->status, 'in_progress'))->count();
        $completed = $assignments->filter(fn ($a) => EnumHelper::is($a->status, 'completed'))->count();
        $staffing = $assignments->filter(fn ($a) => EnumHelper::isNotOneOf($a->status, ['completed', 'no_show']))->count();

        if ($inProgress > 0) {
            $shift->update(['status' => ShiftStatus::IN_PROGRESS->value]);

            return;
        }

        if ($completed >= $shift->required_guards && $staffing === 0) {
            $shift->update(['status' => ShiftStatus::COMPLETED->value]);

            return;
        }

        if ($staffing >= $shift->required_guards) {
            $shift->update(['status' => ShiftStatus::ASSIGNED->value]);

            return;
        }

        $shift->update(['status' => ShiftStatus::OPEN->value]);
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
