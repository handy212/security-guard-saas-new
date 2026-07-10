<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Guard;
use App\Models\Timesheet;
use Illuminate\Support\Carbon;

class PayrollService
{
    public function generateTimesheet(Guard $guard, string $periodStart, string $periodEnd): Timesheet
    {
        $minutes = AttendanceLog::where('guard_id', $guard->id)
            ->whereBetween('clock_in_at', [$periodStart, $periodEnd])
            ->sum('worked_minutes');
        $hours = round($minutes / 60, 2);
        $monthlyRate = (float) ($guard->monthly_rate ?? 0);

        $start = Carbon::parse($periodStart)->startOfDay();
        $end = Carbon::parse($periodEnd)->startOfDay();
        $periodDays = max(1, $start->diffInDays($end) + 1);
        $daysInMonth = $start->daysInMonth;
        $grossPay = round($monthlyRate * ($periodDays / $daysInMonth), 2);

        return Timesheet::create([
            'tenant_id' => $guard->tenant_id,
            'guard_id' => $guard->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'regular_hours' => min($hours, 160),
            'overtime_hours' => max(0, $hours - 160),
            'gross_pay' => $grossPay,
            'status' => 'draft',
        ]);
    }
}
