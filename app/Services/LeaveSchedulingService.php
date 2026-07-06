<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Models\Guard;
use App\Models\LeaveRequest;
use App\Models\ShiftAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LeaveSchedulingService
{
    public function overlappingShiftsForLeave(LeaveRequest $leave): Collection
    {
        if (! $leave->guard_id || ! $leave->starts_on || ! $leave->ends_on) {
            return collect();
        }

        $rangeStart = $leave->starts_on->copy()->startOfDay();
        $rangeEnd = $leave->ends_on->copy()->endOfDay();

        return ShiftAssignment::query()
            ->where('guard_id', $leave->guard_id)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->whereHas('shift', function ($query) use ($rangeStart, $rangeEnd) {
                $query->whereNotIn('status', ['cancelled', 'completed'])
                    ->where('starts_at', '<=', $rangeEnd)
                    ->where('ends_at', '>=', $rangeStart);
            })
            ->with(['shift.site'])
            ->get();
    }

    public function hasApprovedLeaveConflict(Guard $guard, Carbon $shiftStart, Carbon $shiftEnd): bool
    {
        return LeaveRequest::query()
            ->where('guard_id', $guard->id)
            ->where('status', LeaveStatus::APPROVED)
            ->where('starts_on', '<=', $shiftEnd->toDateString())
            ->where('ends_on', '>=', $shiftStart->toDateString())
            ->exists();
    }

    /** @return Collection<int, LeaveRequest> */
    public function approvedLeaveForGuardOnDate(int $guardId, string $date): Collection
    {
        return LeaveRequest::query()
            ->where('guard_id', $guardId)
            ->where('status', LeaveStatus::APPROVED)
            ->where('starts_on', '<=', $date)
            ->where('ends_on', '>=', $date)
            ->get();
    }
}
