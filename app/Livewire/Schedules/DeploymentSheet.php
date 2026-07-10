<?php

namespace App\Livewire\Schedules;

use App\Models\ShiftAssignment;
use App\Support\TenantContext;
use Carbon\Carbon;
use Livewire\Component;

class DeploymentSheet extends Component
{
    public string $date = '';

    protected $queryString = ['date'];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $this->date = $this->date ?: today()->toDateString();
    }

    public function previousDay(): void
    {
        $this->date = Carbon::parse($this->date)->subDay()->toDateString();
    }

    public function nextDay(): void
    {
        $this->date = Carbon::parse($this->date)->addDay()->toDateString();
    }

    public function goToday(): void
    {
        $this->date = today()->toDateString();
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        $assignments = ShiftAssignment::with(['shift.site', 'shift.sitePost', 'assignedGuard'])
            ->where('tenant_id', $tenantId)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->whereHas('shift', fn ($q) => $q->whereDate('starts_at', $this->date)->whereNotIn('status', ['cancelled', 'completed']))
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
