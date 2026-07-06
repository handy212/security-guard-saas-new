<?php

namespace App\Services;

use App\Models\Guard;
use App\Models\OpenShiftBid;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftSwapRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Support\EnumHelper;
use RuntimeException;

class EnterpriseScheduleService
{
    public function openShiftsForGuard(Guard $guard): Collection
    {
        return app(SchedulingService::class)
            ->openShifts($guard->tenant_id, today()->toDateString())
            ->filter(function (Shift $shift) use ($guard) {
                return ! $shift->assignments
                    ->contains(fn ($assignment) => $assignment->guard_id === $guard->id
                        && EnumHelper::isNotOneOf($assignment->status, ['cancelled', 'completed']));
            })
            ->values();
    }

    public function guardBids(Guard $guard): Collection
    {
        return OpenShiftBid::with(['shift.site'])
            ->where('guard_id', $guard->id)
            ->where('tenant_id', $guard->tenant_id)
            ->latest()
            ->limit(20)
            ->get();
    }

    public function guardSwapRequests(Guard $guard): Collection
    {
        return ShiftSwapRequest::with(['shiftAssignment.shift.site', 'replacementGuard'])
            ->where('tenant_id', $guard->tenant_id)
            ->where('requested_by_guard_id', $guard->id)
            ->latest()
            ->limit(20)
            ->get();
    }

    public function requestSwap(ShiftAssignment $assignment, Guard $requestingGuard, ?Guard $replacementGuard = null, ?string $reason = null): ShiftSwapRequest
    {
        if ($assignment->guard_id !== $requestingGuard->id) {
            throw new RuntimeException('You can only request a swap for your own assignments.');
        }

        if ($replacementGuard && $replacementGuard->id === $requestingGuard->id) {
            throw new RuntimeException('Replacement must be a different guard.');
        }

        if ($replacementGuard && $replacementGuard->tenant_id !== $requestingGuard->tenant_id) {
            throw new RuntimeException('Replacement guard is not in your organization.');
        }

        if (ShiftSwapRequest::query()
            ->where('shift_assignment_id', $assignment->id)
            ->where('requested_by_guard_id', $requestingGuard->id)
            ->where('status', 'pending')
            ->exists()) {
            throw new RuntimeException('A swap request is already pending for this shift.');
        }

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

            $assignment = $swap->shiftAssignment;
            $schedule = app(ScheduleService::class);

            if ($swap->replacement_guard_id) {
                $assignment->update([
                    'guard_id' => $swap->replacement_guard_id,
                    'status' => 'assigned',
                    'assigned_at' => now(),
                    'confirmed_at' => null,
                ]);
                app(WorkforceService::class)->requestConfirmation($assignment->fresh());
            } else {
                $schedule->unassignGuard($assignment);
            }
        });
    }

    public function rejectSwap(ShiftSwapRequest $swap): void
    {
        $swap->update(['status' => 'rejected']);
    }

    public function bidForOpenShift(Shift $shift, Guard $guard, ?string $notes = null): OpenShiftBid
    {
        if ($guard->verification_status !== 'verified') {
            throw new RuntimeException('Guard must be verified before bidding on open shifts.');
        }

        if ($shift->tenant_id !== $guard->tenant_id) {
            throw new RuntimeException('Shift is not available.');
        }

        if (EnumHelper::isOneOf($shift->status, ['cancelled', 'completed'])) {
            throw new RuntimeException('This shift is no longer available.');
        }

        $schedule = app(ScheduleService::class);

        if ($shift->assignments()->whereNotIn('status', ['cancelled', 'completed'])->count() >= $shift->required_guards) {
            throw new RuntimeException('This shift is already fully staffed.');
        }

        if ($shift->assignments()->where('guard_id', $guard->id)->whereNotIn('status', ['cancelled', 'completed'])->exists()) {
            throw new RuntimeException('You are already assigned to this shift.');
        }

        if ($schedule->hasConflict($guard, $shift->starts_at, $shift->ends_at, $shift->id)) {
            throw new RuntimeException('You have a scheduling conflict for this shift.');
        }

        return OpenShiftBid::firstOrCreate(
            ['shift_id' => $shift->id, 'guard_id' => $guard->id],
            ['tenant_id' => $shift->tenant_id, 'notes' => $notes, 'status' => 'pending']
        );
    }

    public function approveBid(OpenShiftBid $bid): ShiftAssignment
    {
        return DB::transaction(function () use ($bid) {
            $bid->load(['shift.assignments', 'assignedGuard']);
            $shift = $bid->shift;
            $guard = $bid->assignedGuard;

            if (! $shift || ! $guard) {
                throw new RuntimeException('Bid is missing shift or guard details.');
            }

            if ($guard->verification_status !== 'verified') {
                throw new RuntimeException('Guard must be verified before assignment.');
            }

            $schedule = app(ScheduleService::class);

            if ($schedule->hasConflict($guard, $shift->starts_at, $shift->ends_at, $shift->id)) {
                throw new RuntimeException('Guard has a scheduling conflict for this shift.');
            }

            if ($shift->assignments()->whereNotIn('status', ['cancelled', 'completed'])->count() >= $shift->required_guards) {
                throw new RuntimeException('This shift is already fully staffed.');
            }

            $bid->update(['status' => 'approved']);

            OpenShiftBid::where('shift_id', $shift->id)
                ->where('id', '!=', $bid->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);

            $assignment = ShiftAssignment::create([
                'tenant_id' => $bid->tenant_id,
                'shift_id' => $bid->shift_id,
                'guard_id' => $bid->guard_id,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);

            app(WorkforceService::class)->requestConfirmation($assignment);
            $schedule->refreshShiftStaffingStatus($shift);

            return $assignment;
        });
    }

    public function rejectBid(OpenShiftBid $bid): void
    {
        $bid->update(['status' => 'rejected']);
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
