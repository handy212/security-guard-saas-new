<?php

namespace App\Livewire\Schedules;

use App\Models\Shift;
use App\Models\Site;
use App\Support\TenantContext;
use Carbon\Carbon;
use Livewire\Component;

class CalendarView extends Component
{
    public string $view = 'month';

    public string $cursorDate;

    public ?int $siteId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $this->cursorDate = now()->toDateString();
    }

    public function previous(): void
    {
        $date = Carbon::parse($this->cursorDate);
        $this->cursorDate = ($this->view === 'week' ? $date->subWeek() : $date->subMonth())->toDateString();
    }

    public function next(): void
    {
        $date = Carbon::parse($this->cursorDate);
        $this->cursorDate = ($this->view === 'week' ? $date->addWeek() : $date->addMonth())->toDateString();
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $anchor = Carbon::parse($this->cursorDate);
        $rangeStart = $this->view === 'week' ? $anchor->copy()->startOfWeek() : $anchor->copy()->startOfMonth()->startOfWeek();
        $rangeEnd = $this->view === 'week' ? $anchor->copy()->endOfWeek() : $anchor->copy()->endOfMonth()->endOfWeek();

        $shifts = Shift::with(['site', 'assignments'])
            ->where('tenant_id', $tenantId)
            ->when($this->siteId, fn ($q) => $q->where('site_id', $this->siteId))
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd])
            ->orderBy('starts_at')
            ->get();

        $openShifts = $shifts->filter(fn ($shift) => $shift->assignments->count() < $shift->required_guards)->count();
        $scheduledPosts = $shifts->whereNotNull('site_post_id')->count();

        return view('livewire.schedules.calendar-view', [
            'shifts' => $shifts,
            'sites' => Site::where('tenant_id', $tenantId)->orderBy('name')->get(),
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'stats' => [
                'total' => $shifts->count(),
                'open' => $openShifts,
                'posts' => $scheduledPosts,
            ],
        ])->layout('layouts.app');
    }
}
