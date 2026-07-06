<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use App\Models\OpenShiftBid;
use App\Models\Shift;
use App\Models\ShiftConfirmation;
use App\Models\ShiftSwapRequest;
use Illuminate\Support\Collection;

class SchedulingService
{
    public function overviewStats(int $tenantId, ?string $date = null): array
    {
        $date = $date ?? today()->toDateString();
        $shifts = Shift::where('tenant_id', $tenantId)->whereDate('starts_at', $date)->with('assignments')->get();

        return [
            'shifts_today' => $shifts->count(),
            'open_shifts' => $this->openShifts($tenantId, $date)->count(),
            'staffed' => $shifts->filter(fn (Shift $s) => $this->activeAssignmentCount($s) >= $s->required_guards)->count(),
            'pending_confirmations' => ShiftConfirmation::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'pending_leave' => LeaveRequest::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'pending_swaps' => ShiftSwapRequest::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'pending_bids' => OpenShiftBid::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'on_duty' => AttendanceLog::where('tenant_id', $tenantId)->whereNull('clock_out_at')->count(),
        ];
    }

    public function openShifts(int $tenantId, ?string $fromDate = null): Collection
    {
        return Shift::query()
            ->with(['site', 'sitePost', 'assignments.assignedGuard'])
            ->where('tenant_id', $tenantId)
            ->when($fromDate, fn ($q) => $q->whereDate('starts_at', '>=', $fromDate))
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->orderBy('starts_at')
            ->get()
            ->filter(fn (Shift $shift) => $this->activeAssignmentCount($shift) < $shift->required_guards)
            ->values();
    }

    public function activeAssignmentCount(Shift $shift): int
    {
        return $shift->activeAssignmentsCount();
    }

    public function markShiftOpen(Shift $shift): Shift
    {
        $shift->update(['status' => 'open']);

        return $shift;
    }
}
