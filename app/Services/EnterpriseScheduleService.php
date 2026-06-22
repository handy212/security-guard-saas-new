<?php

namespace App\Services;

use App\Models\{Guard, OpenShiftBid, Shift, ShiftAssignment, ShiftSwapRequest};
use Illuminate\Support\Facades\DB;

class EnterpriseScheduleService
{
    public function requestSwap(ShiftAssignment $assignment, Guard $requestingGuard, ?Guard $replacementGuard, ?string $reason = null): ShiftSwapRequest
    {
        return ShiftSwapRequest::create([
            'tenant_id' => $assignment->tenant_id,
            'shift_assignment_id' => $assignment->id,
            'requested_by_guard_id' => $requestingGuard->id,
            'replacement_guard_id' => $replacementGuard?->id,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }

    public function approveSwap(ShiftSwapRequest $swap, int $approvedBy): void
    {
        DB::transaction(function () use ($swap, $approvedBy) {
            $swap->update(['status' => 'approved', 'approved_by' => $approvedBy, 'approved_at' => now()]);
            if ($swap->replacement_guard_id) {
                $swap->shiftAssignment->update(['guard_id' => $swap->replacement_guard_id, 'status' => 'assigned']);
            }
        });
    }

    public function bidForOpenShift(Shift $shift, Guard $guard, ?string $notes = null): OpenShiftBid
    {
        return OpenShiftBid::firstOrCreate(
            ['shift_id' => $shift->id, 'guard_id' => $guard->id],
            ['tenant_id' => $shift->tenant_id, 'notes' => $notes, 'status' => 'pending']
        );
    }

    public function approveBid(OpenShiftBid $bid): ShiftAssignment
    {
        return DB::transaction(function () use ($bid) {
            $bid->update(['status' => 'approved']);
            return ShiftAssignment::create([
                'tenant_id' => $bid->tenant_id,
                'shift_id' => $bid->shift_id,
                'guard_id' => $bid->guard_id,
                'status' => 'assigned',
            ]);
        });
    }

    public function overtimeHours(Guard $guard, string $weekStart): float
    {
        $minutes = ShiftAssignment::query()
            ->join('shifts', 'shifts.id', '=', 'shift_assignments.shift_id')
            ->where('shift_assignments.guard_id', $guard->id)
            ->whereBetween('shifts.starts_at', [$weekStart, now()->parse($weekStart)->addDays(7)])
            ->selectRaw('SUM(TIMESTAMPDIFF(MINUTE, shifts.starts_at, shifts.ends_at)) as minutes')
            ->value('minutes') ?? 0;
        return max(0, round(($minutes / 60) - 40, 2));
    }
}
