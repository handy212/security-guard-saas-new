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
    /** @return array{created: int, existing: int, overlapping: int, missing_rate: int} */
    public function preparePeriod(int $tenantId, string $periodStart, string $periodEnd): array
    {
        $start = Carbon::parse($periodStart)->startOfDay();
        $end = Carbon::parse($periodEnd)->endOfDay();
        $summary = ['created' => 0, 'existing' => 0, 'overlapping' => 0, 'missing_rate' => 0];
        $attendance = AttendanceLog::forTenant($tenantId)->whereBetween('clock_in_at', [$start, $end])
            ->where('worked_minutes', '>', 0);

        Guard::forTenant($tenantId)->whereIn('id', (clone $attendance)->select('guard_id'))
            ->select('id')->chunkById(200, function ($guards) use ($tenantId, $start, $end, $attendance, &$summary): void {
                $counts = DB::transaction(function () use ($guards, $tenantId, $start, $end, $attendance): array {
                    $counts = ['created' => 0, 'existing' => 0, 'overlapping' => 0, 'missing_rate' => 0];
                    $ids = $guards->modelKeys();
                    $lockedGuards = Guard::forTenant($tenantId)->whereKey($ids)->orderBy('id')->lockForUpdate()->get();
                    $minutes = (clone $attendance)->whereIn('guard_id', $ids)->groupBy('guard_id')
                        ->selectRaw('guard_id, SUM(worked_minutes) AS total_minutes')->pluck('total_minutes', 'guard_id');
                    $sheets = Timesheet::forTenant($tenantId)->whereIn('guard_id', $ids)
                        ->whereDate('period_start', '<=', $end->toDateString())
                        ->whereDate('period_end', '>=', $start->toDateString())
                        ->lockForUpdate()->get()->groupBy('guard_id');
                    $rows = [];
                    $timestamp = now();

                    foreach ($lockedGuards as $guard) {
                        $guardSheets = $sheets->get($guard->id, collect());
                        if ($guardSheets->contains(fn (Timesheet $sheet): bool => $sheet->period_start->isSameDay($start) && $sheet->period_end->isSameDay($end))) {
                            $counts['existing']++;
                        } elseif ($guardSheets->whereIn('status', ['pending', 'approved'])->isNotEmpty()) {
                            $counts['overlapping']++;
                        } elseif ((float) $guard->monthly_rate <= 0) {
                            $counts['missing_rate']++;
                        } elseif ($minutes->has($guard->id)) {
                            $rows[] = [
                                'tenant_id' => $tenantId, 'guard_id' => $guard->id,
                                'period_start' => $start->toDateString(), 'period_end' => $end->toDateString(),
                                'created_at' => $timestamp, 'updated_at' => $timestamp,
                                ...$this->timesheetValues($guard, $start, $end, (int) $minutes[$guard->id]),
                            ];
                        }
                    }

                    if ($rows !== []) {
                        Timesheet::query()->insert($rows);
                    }
                    $counts['created'] = count($rows);

                    return $counts;
                }, attempts: 3);

                foreach ($counts as $key => $count) {
                    $summary[$key] += $count;
                }
            });

        return $summary;
    }

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

            $overlap = Timesheet::forTenant($guard->tenant_id)->where('guard_id', $guard->id)
                ->whereIn('status', ['pending', 'approved'])
                ->when($existing, fn ($query) => $query->whereKeyNot($existing->id))
                ->whereDate('period_start', '<=', $end->toDateString())
                ->whereDate('period_end', '>=', $start->toDateString())->exists();
            if ($overlap) {
                throw new RuntimeException('This period overlaps a pending or approved timesheet for the guard.');
            }

            $minutes = AttendanceLog::where('guard_id', $guard->id)
                ->whereBetween('clock_in_at', [$start, $end])
                ->sum('worked_minutes');
            $values = $this->timesheetValues($guard, $start, $end, (int) $minutes);

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

    private function timesheetValues(Guard $guard, Carbon $start, Carbon $end, int $minutes): array
    {
        $hours = round($minutes / 60, 2);
        $monthlyRate = (float) ($guard->monthly_rate ?? 0);
        $grossPay = 0;
        $lastDay = $end->copy()->startOfDay();
        for ($day = $start->copy(); $day->lte($lastDay); $day = $monthEnd->copy()->addDay()) {
            $monthEnd = $day->copy()->endOfMonth()->startOfDay()->min($lastDay);
            $grossPay += $monthlyRate * (($day->diffInDays($monthEnd) + 1) / $day->daysInMonth);
        }

        return [
            'regular_hours' => min($hours, 160),
            'overtime_hours' => max(0, $hours - 160),
            'gross_pay' => round($grossPay, 2),
            'status' => 'pending',
            'approved_by_user_id' => null,
            'approved_at' => null,
        ];
    }
}
