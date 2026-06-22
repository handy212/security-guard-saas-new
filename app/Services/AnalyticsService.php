<?php

namespace App\Services;

use App\Models\AnalyticsSnapshot;
use App\Models\Guard;
use App\Models\Incident;
use App\Models\Invoice;
use App\Models\PatrolSession;
use App\Models\ShiftAssignment;
use App\Models\Site;

class AnalyticsService
{
    public function snapshot(int $tenantId, ?string $date = null): AnalyticsSnapshot
    {
        $date = $date ?: today()->toDateString();
        $completed = PatrolSession::where('tenant_id', $tenantId)->whereDate('created_at', $date)->where('status', 'completed')->count();
        $total = PatrolSession::where('tenant_id', $tenantId)->whereDate('created_at', $date)->count();
        $incidents = Incident::where('tenant_id', $tenantId)->whereDate('created_at', $date)->selectRaw('severity, COUNT(*) as total')->groupBy('severity')->pluck('total', 'severity')->toArray();

        return AnalyticsSnapshot::updateOrCreate(
            ['tenant_id' => $tenantId, 'snapshot_date' => $date],
            [
                'active_guards' => Guard::where('tenant_id', $tenantId)->where('status', 'active')->count(),
                'active_sites' => Site::where('tenant_id', $tenantId)->where('status', 'active')->count(),
                'missed_patrols' => PatrolSession::where('tenant_id', $tenantId)->whereDate('created_at', $date)->where('status', 'missed')->count(),
                'incidents_by_severity' => $incidents,
                'late_shifts' => ShiftAssignment::where('tenant_id', $tenantId)->where('status', 'late')->count(),
                'no_show_shifts' => ShiftAssignment::where('tenant_id', $tenantId)->where('status', 'no_show')->count(),
                'patrol_completion_rate' => $total ? round(($completed / $total) * 100, 2) : 0,
                'client_sla_performance' => 0,
                'revenue_total' => Invoice::where('tenant_id', $tenantId)->whereDate('created_at', $date)->sum('grand_total'),
                'guard_scores' => [],
            ]
        );
    }
}
