<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\PayrollExport;
use App\Models\Timesheet;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemException;
use RuntimeException;

class PayrollExportService
{
    public function exportQuickBooks(string $periodStart, string $periodEnd, int $userId, bool $exactPeriod = false): PayrollExport
    {
        $tenantId = TenantContext::id();
        $start = Carbon::parse($periodStart)->startOfDay();
        $end = Carbon::parse($periodEnd)->endOfDay();
        if ($start->greaterThan($end)) {
            throw new RuntimeException('Payroll end date must be on or after the start date.');
        }

        $timesheets = Timesheet::with('assignedGuard')
            ->where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->whereDate('period_start', $exactPeriod ? '=' : '>=', $start->toDateString())
            ->whereDate('period_end', $exactPeriod ? '=' : '<=', $end->toDateString())
            ->get();

        if ($timesheets->isEmpty()) {
            throw new RuntimeException('Approve at least one timesheet before exporting payroll.');
        }

        $rows = [['Employee', 'Period start', 'Period end', 'Regular hours', 'Overtime hours', 'Gross pay']];
        foreach ($timesheets as $timesheet) {
            $rows[] = [
                $timesheet->assignedGuard?->full_name ?? 'Guard',
                $timesheet->period_start?->toDateString() ?? '',
                $timesheet->period_end?->toDateString() ?? '',
                number_format((float) $timesheet->regular_hours, 2, '.', ''),
                number_format((float) $timesheet->overtime_hours, 2, '.', ''),
                number_format((float) $timesheet->gross_pay, 2, '.', ''),
            ];
        }

        $csv = collect($rows)
            ->map(fn ($row) => implode(',', array_map($this->quoteCsv(...), $row)))
            ->implode("\n");
        $path = "payroll/{$tenantId}/quickbooks-{$start->toDateString()}-{$end->toDateString()}.csv";
        try {
            $saved = Storage::put($path, $csv);
        } catch (FilesystemException $exception) {
            report($exception);
            $saved = false;
        }
        if (! $saved) {
            throw new RuntimeException('Payroll CSV could not be saved to private storage. Ask your administrator to check storage permissions.');
        }

        return PayrollExport::updateOrCreate([
            'tenant_id' => $tenantId,
            'provider' => 'quickbooks',
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
        ], [
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

    private function quoteCsv(mixed $value): string
    {
        $value = (string) $value;
        if (preg_match('/^[=+\-@]/', $value)) {
            $value = "'".$value;
        }

        return '"'.str_replace('"', '""', $value).'"';
    }
}
