<?php

namespace App\Services;

use App\Models\ClientReportApproval;
use App\Models\DailyActivityReport;
use App\Models\ShiftAssignment;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportService
{
    public function submitForShift(ShiftAssignment $assignment, string $summary, ?string $handover): DailyActivityReport
    {
        return DB::transaction(function () use ($assignment, $summary, $handover): DailyActivityReport {
            ShiftAssignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();

            $report = DailyActivityReport::query()
                ->where('tenant_id', $assignment->tenant_id)
                ->where('shift_assignment_id', $assignment->id)
                ->oldest('id')
                ->first();

            if ($report && $report->status !== 'draft') {
                return $report;
            }

            $report ??= new DailyActivityReport;
            $report->fill([
                'tenant_id' => $assignment->tenant_id,
                'shift_assignment_id' => $assignment->id,
                'guard_id' => $assignment->guard_id,
                'site_id' => $assignment->shift->site_id,
                'report_date' => $assignment->shift->starts_at->toDateString(),
                'title' => 'Shift activity report',
                'summary' => $summary,
                'handover_notes' => $handover,
                'status' => 'submitted',
            ]);
            $report->submitted_at = now();
            $report->save();

            return $report;
        });
    }

    public function approve(DailyActivityReport $report, int $userId): DailyActivityReport
    {
        return DB::transaction(function () use ($report, $userId): DailyActivityReport {
            $approved = DailyActivityReport::query()->whereKey($report->id)
                ->where('status', 'submitted')
                ->update([
                    'status' => 'approved',
                    'approved_by_user_id' => $userId,
                    'approved_at' => now(),
                ]);

            if (! $approved) {
                throw ValidationException::withMessages(['approval' => 'Only submitted reports can be approved.']);
            }

            if ($clientId = $report->site?->client_account_id) {
                ClientReportApproval::firstOrCreate([
                    'tenant_id' => $report->tenant_id,
                    'client_account_id' => $clientId,
                    'approvable_type' => $report->getMorphClass(),
                    'approvable_id' => $report->id,
                ], ['status' => 'pending']);
            }

            return $report->refresh();
        });
    }

    public function listForTenant(): Collection
    {
        return DailyActivityReport::query()
            ->where('tenant_id', TenantContext::id())
            ->latest()
            ->limit(50)
            ->get();
    }
}
