<?php

namespace App\Livewire\Scheduling;

use App\Enums\LeaveStatus;
use App\Enums\ShiftStatus;
use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Livewire\Concerns\HasFormDrawer;
use App\Models\ClientAccount;
use App\Models\Guard;
use App\Models\LeaveRequest;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\Site;
use App\Models\SitePost;
use App\Services\ScheduleService;
use App\Services\SchedulingService;
use App\Support\EnumHelper;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Carbon\Carbon;
use Livewire\Component;
use RuntimeException;

class ScheduleIndex extends Component
{
    use AuthorizesModuleAccess, HasFormDrawer;

    public string $date = '';

    protected $queryString = ['date'];

    public ?int $editingShiftId = null;

    public array $form = [
        'client_account_id' => '', 'site_id' => '', 'site_post_id' => '', 'title' => '',
        'starts_at' => '', 'ends_at' => '', 'required_guards' => 1, 'billing_rate' => 0,
        'notes' => '',
    ];

    public array $pendingGuard = [];

    public function mount(): void
    {
        $this->authorizePolicy('viewAny', Shift::class);
        $this->date = $this->date ?: today()->toDateString();
        $this->resetFormDefaults();
    }

    public function updatedFormClientAccountId(): void
    {
        $this->form['site_id'] = '';
        $this->form['site_post_id'] = '';
    }

    public function updatedFormSiteId(): void
    {
        $this->form['site_post_id'] = '';
    }

    public function openForm(): void
    {
        $this->editingShiftId = null;
        $this->resetFormDefaults();
        $this->showForm = true;
    }

    public function editShift(int $shiftId): void
    {
        $shift = Shift::findOrFail($shiftId);
        $this->authorize('update', $shift);

        $this->editingShiftId = $shift->id;
        $this->form = [
            'client_account_id' => (string) ($shift->client_account_id ?? ''),
            'site_id' => (string) ($shift->site_id ?? ''),
            'site_post_id' => (string) ($shift->site_post_id ?? ''),
            'title' => $shift->title,
            'starts_at' => $shift->starts_at?->format('Y-m-d\TH:i') ?? '',
            'ends_at' => $shift->ends_at?->format('Y-m-d\TH:i') ?? '',
            'required_guards' => $shift->required_guards,
            'billing_rate' => $shift->billing_rate,
            'notes' => $shift->notes ?? '',
        ];
        $this->showForm = true;
    }

    public function save(ScheduleService $service): void
    {
        $data = $this->validatedForm();

        if ($this->editingShiftId) {
            $shift = Shift::findOrFail($this->editingShiftId);
            $this->authorize('update', $shift);
            $data['status'] = EnumHelper::value($shift->status);
            $service->updateShift($shift, $data);
            session()->flash('status', 'Shift updated.');
        } else {
            $this->authorize('create', Shift::class);
            $data['status'] = ShiftStatus::OPEN->value;
            $service->createShift($data + ['tenant_id' => TenantContext::id()]);
            session()->flash('status', 'Shift created.');
        }

        $this->showForm = false;
        $this->editingShiftId = null;
        $this->resetFormDefaults();
    }

    public function cancelShift(int $shiftId, ScheduleService $service): void
    {
        $shift = Shift::findOrFail($shiftId);
        $this->authorize('delete', $shift);
        $service->cancelShift($shift);
        session()->flash('status', 'Shift cancelled.');
    }

    public function assignGuard(int $shiftId, ScheduleService $service): void
    {
        $this->validate([
            "pendingGuard.{$shiftId}" => ['required', TenantValidation::exists('guards')],
        ], [
            "pendingGuard.{$shiftId}.required" => 'Select a guard before assigning.',
        ]);

        $shift = Shift::findOrFail($shiftId);
        $this->authorize('assign', $shift);

        try {
            $service->assignGuard($shift, Guard::findOrFail((int) $this->pendingGuard[$shiftId]));
        } catch (RuntimeException $e) {
            $this->addError("pendingGuard.{$shiftId}", $e->getMessage());

            return;
        }

        unset($this->pendingGuard[$shiftId]);
        session()->flash('status', 'Guard assigned.');
    }

    public function unassignGuard(int $assignmentId, ScheduleService $service): void
    {
        $assignment = ShiftAssignment::with('shift')->findOrFail($assignmentId);
        $this->authorize('assign', $assignment->shift);
        $service->unassignGuard($assignment);
        session()->flash('status', 'Guard unassigned.');
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

    public function render(SchedulingService $scheduling)
    {
        $tenantId = TenantContext::id();
        $shifts = Shift::with(['site', 'sitePost', 'assignments.assignedGuard'])
            ->where('tenant_id', $tenantId)
            ->whereDate('starts_at', $this->date)
            ->where('status', '!=', ShiftStatus::CANCELLED->value)
            ->orderBy('starts_at')
            ->get();

        $sitesForClient = $this->form['client_account_id']
            ? Site::where('client_account_id', $this->form['client_account_id'])->orderBy('name')->get()
            : collect();

        $postsForSite = $this->form['site_id']
            ? SitePost::where('site_id', $this->form['site_id'])->orderBy('name')->get()
            : collect();

        $guardsOnLeaveIds = LeaveRequest::query()
            ->where('tenant_id', $tenantId)
            ->where('status', LeaveStatus::APPROVED)
            ->where('starts_on', '<=', $this->date)
            ->where('ends_on', '>=', $this->date)
            ->pluck('guard_id')
            ->all();

        return view('livewire.scheduling.schedule-index', [
            'shifts' => $shifts,
            'stats' => $scheduling->overviewStats($tenantId, $this->date),
            'clients' => ClientAccount::orderBy('name')->get(),
            'sitesForClient' => $sitesForClient,
            'postsForSite' => $postsForSite,
            'guards' => Guard::where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where('verification_status', 'verified')
                ->orderBy('first_name')
                ->get(),
            'unverifiedGuardCount' => Guard::where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where('verification_status', '!=', 'verified')
                ->count(),
            'guardsOnLeaveIds' => $guardsOnLeaveIds,
        ])->layout('layouts.app');
    }

    private function validatedForm(): array
    {
        $data = $this->validate([
            'form.client_account_id' => ['required', TenantValidation::exists('client_accounts')],
            'form.site_id' => ['required', TenantValidation::exists('sites')],
            'form.site_post_id' => ['nullable', TenantValidation::exists('site_posts')],
            'form.title' => 'required|string|max:255',
            'form.starts_at' => 'required|date',
            'form.ends_at' => 'required|date|after:form.starts_at',
            'form.required_guards' => 'integer|min:1',
            'form.billing_rate' => 'numeric|min:0',
            'form.notes' => 'nullable|string',
        ])['form'];

        $site = Site::findOrFail($data['site_id']);
        abort_unless((int) $site->client_account_id === (int) $data['client_account_id'], 422, 'Selected site does not belong to the client.');

        $data['site_post_id'] = filled($data['site_post_id'] ?? null) ? $data['site_post_id'] : null;
        $data['notes'] = filled($data['notes'] ?? null) ? $data['notes'] : null;

        return $data;
    }

    private function resetFormDefaults(): void
    {
        $anchor = Carbon::parse($this->date ?: today()->toDateString());

        $this->form = [
            'client_account_id' => '',
            'site_id' => '',
            'site_post_id' => '',
            'title' => '',
            'starts_at' => $anchor->copy()->setTime(8, 0)->format('Y-m-d\TH:i'),
            'ends_at' => $anchor->copy()->setTime(17, 0)->format('Y-m-d\TH:i'),
            'required_guards' => 1,
            'billing_rate' => 0,
            'notes' => '',
        ];
    }
}
