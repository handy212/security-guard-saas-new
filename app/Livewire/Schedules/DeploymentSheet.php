<?php

namespace App\Livewire\Schedules;

use App\Models\ShiftAssignment;
use App\Support\TenantContext;
use Livewire\Component;

class DeploymentSheet extends Component
{
    public string $date;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $this->date = today()->toDateString();
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        $assignments = ShiftAssignment::with(['shift.site', 'shift.sitePost', 'assignedGuard'])
            ->where('tenant_id', $tenantId)
            ->whereHas('shift', fn ($q) => $q->whereDate('starts_at', $this->date))
            ->get()
            ->sortBy(fn ($a) => $a->shift?->starts_at);

        $sites = $assignments->pluck('shift.site')->filter()->unique('id');
        $guards = $assignments->pluck('assignedGuard')->filter()->unique('id');

        return view('livewire.schedules.deployment-sheet', [
            'assignments' => $assignments,
            'stats' => [
                'assignments' => $assignments->count(),
                'sites' => $sites->count(),
                'guards' => $guards->count(),
            ],
        ])->layout('layouts.app');
    }
}
