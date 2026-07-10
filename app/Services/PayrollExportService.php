<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\PayrollExport;
use App\Models\ShiftAssignment;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;

class PayrollExportService
{
    public function exportQuickBooks(string $periodStart, string $periodEnd, int $userId): PayrollExport
    {
        $tenantId = TenantContext::id();

        $assignments = ShiftAssignment::with(['assignedGuard', 'shift.site'])
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereHas('shift', fn ($q) => $q->whereBetween('starts_at', [$periodStart, $periodEnd]))
            ->get();

        $rows = [['Employee', 'Date', 'Hours', 'Monthly rate', 'Site', 'Amount']];
        foreach ($assignments as $assignment) {
            $hours = max(1, $assignment->shift->billable_hours ?? 8);
            $rate = $assignment->assignedGuard?->monthly_rate ?? 0;
            $rows[] = [
                $assignment->assignedGuard?->full_name ?? 'Guard',
                $assignment->shift->starts_at?->toDateString() ?? '',
                $hours,
                $rate,
                $assignment->shift->site?->name ?? '',
                $rate,
            ];
        }

        $csv = collect($rows)->map(fn ($row) => implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', $v).'"', $row)))->implode("\n");
        $path = "payroll/{$tenantId}/quickbooks-{$periodStart}-{$periodEnd}.csv";
        Storage::put($path, $csv);

        return PayrollExport::create([
            'tenant_id' => $tenantId,
            'provider' => 'quickbooks',
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'file_path' => $path,
            'exported_by_user_id' => $userId,
            'exported_at' => now(),
        ]);
    }

    public function reconcileAttendance(AttendanceLog $log, int $userId, ?string $notes = null, ?string $newStatus = null): AttendanceLog
    {
        $log->update([
            'original_status' => $log->original_status ?? $log->status,
            'status' => $newStatus ?? $log->status,
            'reconciled_at' => now(),
            'reconciled_by_user_id' => $userId,
            'reconciliation_notes' => $notes,
        ]);

        return $log->fresh();
    }
}
