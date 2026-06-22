<?php

namespace App\Policies;

use App\Models\DailyActivityReport;
use App\Models\User;

class DailyActivityReportPolicy
{
    public function approve(User $user, DailyActivityReport $report): bool
    {
        return $user->can('reports.approve') && $user->tenant_id === $report->tenant_id;
    }
}
