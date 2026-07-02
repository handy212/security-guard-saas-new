<?php

namespace App\Livewire\Scheduling;

use App\Livewire\Concerns\HasFormDrawer;
use App\Models\ClientAccount;
use App\Models\Guard;
use App\Models\Shift;
use App\Models\Site;
use App\Models\SitePost;
use App\Services\ScheduleService;
use App\Services\SchedulingService;
use App\Support\TenantContext;
use Livewire\Component;
use RuntimeException;

class ScheduleIndex extends Component
{
    use HasFormDrawer;

    public string $date = '';

    public array $form = [
        'client_account_id' => '', 'site_id' => '', 'site_post_id' => '', 'title' => '',
        'starts_at' => '', 'ends_at' => '', 'required_guards' => 1, 'billing_rate' => 0, 'status' => 'open',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
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
            'form.required_guards' => 'integer|min:1',
            'form.billing_rate' => 'numeric|min:0',
        ])['form'];

        $service->createShift($data + ['tenant_id' => TenantContext::id()]);
        $this->showForm = false;
        session()->flash('status', 'Shift created.');
    }

    public array $pendingGuard = [];

    public function assignGuard(int $shiftId, ScheduleService $service): void
    {
        $guardId = (int) ($this->pendingGuard[$shiftId] ?? 0);
        abort_unless($guardId, 422, 'Select a guard first.');

        $shift = Shift::findOrFail($shiftId);
        $this->authorize('assign', $shift);

        try {
            $service->assignGuard($shift, Guard::findOrFail($guardId));
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        unset($this->pendingGuard[$shiftId]);
        session()->flash('status', 'Guard assigned.');
    }

    public function postOpen(int $shiftId, SchedulingService $scheduling): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $scheduling->markShiftOpen(Shift::findOrFail($shiftId));
        session()->flash('status', 'Shift posted to open shifts.');
    }

    public function render(SchedulingService $scheduling)
    {
        $tenantId = TenantContext::id();
        $shifts = Shift::with(['site', 'sitePost', 'assignments.assignedGuard'])
            ->where('tenant_id', $tenantId)
            ->whereDate('starts_at', $this->date)
            ->orderBy('starts_at')
            ->get();

        return view('livewire.scheduling.schedule-index', [
            'shifts' => $shifts,
            'stats' => $scheduling->overviewStats($tenantId, $this->date),
            'clients' => ClientAccount::orderBy('name')->get(),
            'sites' => Site::orderBy('name')->get(),
            'posts' => SitePost::orderBy('name')->get(),
            'guards' => Guard::where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where('verification_status', 'verified')
                ->orderBy('first_name')
                ->get(),
            'unverifiedGuardCount' => Guard::where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where('verification_status', '!=', 'verified')
                ->count(),
        ])->layout('layouts.app');
    }
}
