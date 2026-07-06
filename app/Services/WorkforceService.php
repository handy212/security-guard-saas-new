<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftConfirmation;
use App\Models\ShiftTemplate;
use App\Support\TenantContext;
use Carbon\Carbon;

class WorkforceService
{
    public function __construct(private NotificationDispatcher $notifications) {}

    public function applyTemplate(ShiftTemplate $template, Carbon $weekStart): int
    {
        $template->load('items.site');
        $weekStart = $weekStart->copy()->startOfWeek(Carbon::SUNDAY);
        $created = 0;

        foreach ($template->items as $item) {
            $date = $weekStart->copy()->addDays((int) $item->day_of_week);
            $startsAt = $date->copy()->setTimeFromTimeString($item->start_time);
            $endsAt = $date->copy()->setTimeFromTimeString($item->end_time);

            if ($endsAt->lte($startsAt)) {
                $endsAt->addDay();
            }

            Shift::create([
                'tenant_id' => TenantContext::id(),
                'client_account_id' => $item->site?->client_account_id,
                'site_id' => $item->site_id,
                'title' => $template->name.' · '.$date->format('D'),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'required_guards' => $item->required_guards,
                'billing_rate' => $item->billing_rate ?? 0,
                'status' => 'open',
            ]);
            $created++;
        }

        return $created;
    }

    public function requestConfirmation(ShiftAssignment $assignment): ShiftConfirmation
    {
        return ShiftConfirmation::firstOrCreate([
            'tenant_id' => $assignment->tenant_id,
            'shift_assignment_id' => $assignment->id,
            'guard_id' => $assignment->guard_id,
        ], ['status' => 'pending']);
    }

    public function confirmShift(ShiftConfirmation $confirmation): ShiftConfirmation
    {
        $confirmation->update(['status' => 'confirmed', 'confirmed_at' => now()]);

        $confirmation->shiftAssignment?->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $this->notifications->sendToTenantAdmins(
            $confirmation->tenant_id,
            'shift.confirmed',
            [
                'guard' => $confirmation->assignedGuard?->full_name ?? 'Guard',
                'shift' => $confirmation->shiftAssignment?->shift?->starts_at?->format('M j, H:i') ?? '',
            ],
            actionUrl: '/schedules/shift-status',
        );

        return $confirmation->fresh();
    }
}
