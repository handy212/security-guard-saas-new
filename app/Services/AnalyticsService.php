<?php

namespace App\Services;

use App\Models\AnalyticsSnapshot;
use App\Models\AttendanceLog;
use App\Models\Guard;
use App\Models\Incident;
use App\Models\Invoice;
use App\Models\PatrolSession;
use App\Models\ShiftAssignment;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AnalyticsService
{
    public function snapshot(int $tenantId, ?string $date = null): AnalyticsSnapshot
    {
        $day = Carbon::parse($date ?: today())->startOfDay();
        $date = $day->toDateString();
        $shiftOnDay = fn (Builder $query) => $query->where('tenant_id', $tenantId)
            ->where('starts_at', '>=', $day)
            ->where('starts_at', '<', $day->copy()->addDay());
        $completed = PatrolSession::where('tenant_id', $tenantId)->whereDate('created_at', $date)->where('status', 'completed')->count();
        $total = PatrolSession::where('tenant_id', $tenantId)->whereDate('created_at', $date)->count();
        $incidents = Incident::where('tenant_id', $tenantId)->whereDate('created_at', $date)->selectRaw('severity, COUNT(*) as total')->groupBy('severity')->pluck('total', 'severity')->toArray();

        $lateAssignments = AttendanceLog::query()
            ->where('attendance_logs.tenant_id', $tenantId)
            ->join('shift_assignments', 'shift_assignments.id', '=', 'attendance_logs.shift_assignment_id')
            ->join('shifts', 'shifts.id', '=', 'shift_assignments.shift_id')
            ->where('shift_assignments.tenant_id', $tenantId)
            ->where('shifts.tenant_id', $tenantId)
            ->where('shifts.starts_at', '>=', $day)
            ->where('shifts.starts_at', '<', $day->copy()->addDay())
            ->whereNotNull('attendance_logs.clock_in_at')
            ->toBase()
            ->select('attendance_logs.id', 'attendance_logs.shift_assignment_id', 'attendance_logs.clock_in_at',
                'attendance_logs.status', 'attendance_logs.original_status', 'shifts.starts_at')
            ->lazyById(column: 'attendance_logs.id', alias: 'id')
            ->filter(fn ($log): bool => ($log->original_status ?? $log->status) === 'late'
                || ($log->status === 'completed' && $log->original_status === null
                    && Carbon::parse($log->clock_in_at)->gt(Carbon::parse($log->starts_at)->addMinutes(10))))
            ->unique('shift_assignment_id')
            ->count();

        $payload = [
            'active_guards' => Guard::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'active_sites' => Site::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'missed_patrols' => PatrolSession::where('tenant_id', $tenantId)->whereDate('created_at', $date)->where('status', 'missed')->count(),
            'incidents_by_severity' => $incidents,
            'late_shifts' => $lateAssignments,
            'no_show_shifts' => ShiftAssignment::where('tenant_id', $tenantId)->where('status', 'no_show')
                ->whereHas('shift', $shiftOnDay)->count(),
            'patrol_completion_rate' => $total ? round(($completed / $total) * 100, 2) : 0,
            'client_sla_performance' => app(CompliancePolicyService::class)->tenantSlaCoverage($tenantId),
            'revenue_total' => Invoice::where('tenant_id', $tenantId)->whereDate('invoice_date', $date)
                ->whereNotIn('status', ['draft', 'void'])->sum('grand_total'),
            'guard_scores' => [],
        ];

        return AnalyticsSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('snapshot_date', $date)
            ->updateOrCreate(['tenant_id' => $tenantId, 'snapshot_date' => $day], $payload);
    }
}
