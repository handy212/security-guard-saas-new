<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Guard;
use App\Models\Timesheet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollService
{
    public function generateTimesheet(Guard $guard, string $periodStart, string $periodEnd): Timesheet
    {
        $start = Carbon::parse($periodStart)->startOfDay();
        $end = Carbon::parse($periodEnd)->endOfDay();
        if ($start->greaterThan($end)) {
            throw new RuntimeException('Period end must be on or after period start.');
        }

        return DB::transaction(function () use ($guard, $start, $end) {
            $guard = Guard::query()->lockForUpdate()->findOrFail($guard->id);
            $existing = Timesheet::query()
                ->where('guard_id', $guard->id)
                ->whereDate('period_start', $start->toDateString())
                ->whereDate('period_end', $end->toDateString())
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->status === 'approved') {
                throw new RuntimeException('An approved timesheet already exists for this guard and period.');
            }

            $minutes = AttendanceLog::where('guard_id', $guard->id)
                ->whereBetween('clock_in_at', [$start, $end])
                ->sum('worked_minutes');
            $hours = round($minutes / 60, 2);
            $monthlyRate = (float) ($guard->monthly_rate ?? 0);
            $periodDays = max(1, $start->diffInDays($end->copy()->startOfDay()) + 1);
            $daysInMonth = $start->daysInMonth;
            $grossPay = round($monthlyRate * ($periodDays / $daysInMonth), 2);

            $values = [
                'regular_hours' => min($hours, 160),
                'overtime_hours' => max(0, $hours - 160),
                'gross_pay' => $grossPay,
                'status' => 'pending',
            ];

            if ($existing) {
                $existing->update($values);

                return $existing->fresh();
            }

            return Timesheet::create([
                'tenant_id' => $guard->tenant_id,
                'guard_id' => $guard->id,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                ...$values,
            ]);
        });
    }
}
