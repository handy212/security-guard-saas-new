<?php

namespace App\Livewire\Shifts;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Livewire\Concerns\HasFormDrawer;
use App\Models\ClientAccount;
use App\Models\Guard;
use App\Models\Shift;
use App\Models\Site;
use App\Models\SitePost;
use App\Services\ScheduleService;
use App\Support\TenantContext;
use Livewire\Component;

class ScheduleBoard extends Component
{
    use AuthorizesModuleAccess, HasFormDrawer;

    public string $date;

    public array $form = [
        'client_account_id' => '', 'site_id' => '', 'site_post_id' => '', 'title' => '',
        'starts_at' => '', 'ends_at' => '', 'required_guards' => 1, 'billing_rate' => 0, 'status' => 'open',
    ];

    public ?int $assignShiftId = null;

    public ?int $assignGuardId = null;

    public function mount(): void
    {
        $this->authorizePolicy('viewAny', Shift::class);
        $this->date = today()->toDateString();
        $this->form['starts_at'] = today()->setHour(8)->format('Y-m-d\TH:i');
        $this->form['ends_at'] = today()->setHour(17)->format('Y-m-d\TH:i');
    }

    public function save(ScheduleService $service): void
    {
        $this->authorize('create', Shift::class);
        $data = $this->validate([
            'form.client_account_id' => 'required',
            'form.site_id' => 'required',
            'form.title' => 'required',
            'form.starts_at' => 'required',
            'form.ends_at' => 'required',
            'form.required_guards' => 'integer',
            'form.billing_rate' => 'numeric',
        ])['form'];
        $service->createShift($data + ['tenant_id' => TenantContext::id()]);
        $this->closeDrawer();
    }

    public function assign(ScheduleService $service): void
    {
        $shift = Shift::findOrFail($this->assignShiftId);
        $this->authorize('assign', $shift);
        $service->assignGuard($shift, Guard::findOrFail($this->assignGuardId));
        $this->reset(['assignShiftId', 'assignGuardId']);
    }

    public function render()
    {
        $shifts = Shift::with(['site', 'sitePost', 'assignments.assignedGuard'])
            ->whereDate('starts_at', $this->date)
            ->orderBy('starts_at')
            ->get();

        $needsGuards = $shifts->filter(fn (Shift $shift) => $shift->assignments->count() < $shift->required_guards)->count();

        return view('livewire.shifts.schedule-board', [
            'shifts' => $shifts,
            'scheduleStats' => [
                'total' => $shifts->count(),
                'open' => $shifts->where('status', 'open')->count(),
                'staffed' => $shifts->filter(fn (Shift $shift) => $shift->assignments->count() >= $shift->required_guards)->count(),
                'needs_guards' => $needsGuards,
            ],
            'clients' => ClientAccount::orderBy('name')->get(),
            'sites' => Site::orderBy('name')->get(),
            'posts' => SitePost::orderBy('name')->get(),
            'guards' => Guard::where('status', 'active')->orderBy('first_name')->get(),
        ])->layout('layouts.app');
    }
}
