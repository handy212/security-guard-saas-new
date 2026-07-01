<?php

namespace App\Services;

use App\Models\{Guard, Shift, ShiftAssignment};
use Carbon\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

class ScheduleService
{
    public function createShift(array $data): Shift
    {
        return Shift::create($data + ['status' => $data['status'] ?? 'open']);
    }

    public function assignGuard(Shift $shift, Guard $guard): ShiftAssignment
    {
        if ($guard->verification_status !== 'verified') {
            throw new RuntimeException('Guard must be verified before assignment. Complete Know Your Guard vetting first.');
        }

        if ($this->hasConflict($guard, $shift->starts_at, $shift->ends_at)) {
            throw new RuntimeException('Guard has another assignment in this time range.');
        }

        $assignment = ShiftAssignment::create([
            'tenant_id' => $shift->tenant_id,
            'shift_id' => $shift->id,
            'guard_id' => $guard->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        $shift->update(['status' => 'assigned']);
        return $assignment;
    }

    public function hasConflict(Guard $guard, string|Carbon $start, string|Carbon $end): bool
    {
        return ShiftAssignment::where('guard_id', $guard->id)
            ->whereHas('shift', function ($query) use ($start, $end) {
                $query->where('starts_at', '<', Carbon::parse($end))
                    ->where('ends_at', '>', Carbon::parse($start))
                    ->whereNotIn('status', ['cancelled','completed']);
            })->exists();
    }

    public function openShiftsForDate(string $date): Collection
    {
        return Shift::with(['site','sitePost','assignments.assignedGuard'])
            ->whereDate('starts_at', $date)
            ->orderBy('starts_at')
            ->get();
    }
}
