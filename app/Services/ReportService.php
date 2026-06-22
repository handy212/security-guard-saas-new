<?php

namespace App\Services;

use App\Models\DailyActivityReport;
use App\Support\TenantContext;

class ReportService
{
    public function approve(DailyActivityReport $report, int $userId): DailyActivityReport
    {
        $report->update([
            'status' => 'approved',
            'approved_by_user_id' => $userId,
            'approved_at' => now(),
        ]);

        return $report->fresh();
    }

    public function listForTenant(): \Illuminate\Database\Eloquent\Collection
    {
        return DailyActivityReport::query()
            ->where('tenant_id', TenantContext::id())
            ->latest()
            ->limit(50)
            ->get();
    }
}
