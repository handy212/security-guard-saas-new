<?php

namespace App\Services;

use App\Models\{Guard, Timesheet, AttendanceLog};

class PayrollService
{
    public function generateTimesheet(Guard $guard, string $periodStart, string $periodEnd): Timesheet
    {
        $minutes = AttendanceLog::where('guard_id', $guard->id)
            ->whereBetween('clock_in_at', [$periodStart, $periodEnd])
            ->sum('worked_minutes');
        $hours = round($minutes / 60, 2);
        $rate = $guard->hourly_rate ?? 0;
        return Timesheet::create([
            'tenant_id' => $guard->tenant_id,
            'guard_id' => $guard->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'regular_hours' => min($hours, 160),
            'overtime_hours' => max(0, $hours - 160),
            'gross_pay' => ($hours * $rate),
            'status' => 'draft',
        ]);
    }
}
