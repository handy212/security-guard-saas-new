<?php

namespace App\Livewire\Settings;

use App\Enums\GuardDutyType;
use App\Livewire\Concerns\HasFormDrawer;
use App\Models\Branch;
use App\Models\DispatchTeam;
use App\Models\Guard;
use App\Models\Site;
use App\Models\Tenant;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class BranchIndex extends Component
{
    use HasFormDrawer, WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public string $teamName = '';

    public ?int $standbySiteId = null;

    public array $teamMembers = [];

    public ?int $pointRegionId = null;

    public ?int $pointSiteId = null;

    public array $form = [
        'name' => '',
        'code' => '',
        'phone' => '',
        'email' => '',
        'address' => '',
        'city' => '',
        'country' => '',
        'is_active' => true,
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $branch = Branch::forTenant(TenantContext::id())->findOrFail($id);
        $this->editingId = $branch->id;
        $this->form = array_merge($this->form, $branch->only(array_keys($this->form)));
        $this->form['is_active'] = (bool) $branch->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);

        $data = $this->validate([
            'form.name' => 'required|string|max:255',
            'form.code' => 'nullable|string|max:50',
            'form.phone' => 'nullable|string|max:50',
            'form.email' => 'nullable|email|max:255',
            'form.address' => 'nullable|string|max:500',
            'form.city' => 'nullable|string|max:120',
            'form.country' => 'nullable|string|max:120',
            'form.is_active' => 'boolean',
        ])['form'];

        if ($this->editingId) {
            Branch::forTenant(TenantContext::id())->findOrFail($this->editingId)->update($data);
        } else {
            Branch::create($data + ['tenant_id' => TenantContext::id()]);
        }

        $this->resetForm();
        $this->showForm = false;
        session()->flash('status', 'Region saved.');
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        Branch::forTenant(TenantContext::id())->findOrFail($id)->delete();
        session()->flash('status', 'Region deleted.');
    }

    public function registerStandbyPoint(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $tenantId = TenantContext::id();
        $data = $this->validate([
            'pointRegionId' => ['required', TenantValidation::exists('branches')->where('is_active', true)],
            'pointSiteId' => ['required', TenantValidation::exists('sites')->where('status', 'active')],
        ]);

        DB::transaction(function () use ($tenantId, $data): void {
            Tenant::query()->lockForUpdate()->findOrFail($tenantId);
            $site = Site::forTenant($tenantId)->lockForUpdate()->findOrFail($data['pointSiteId']);
            if ($site->dispatch_region_id !== null && (int) $site->dispatch_region_id !== $data['pointRegionId']) {
                throw ValidationException::withMessages(['pointSiteId' => 'This standby point already belongs to another region.']);
            }
            $site->forceFill(['dispatch_region_id' => $data['pointRegionId']])->save();
        }, attempts: 3);

        $this->reset('pointRegionId', 'pointSiteId');
        session()->flash('status', 'Dispatch standby point registered.');
    }

    public function removeStandbyPoint(int $siteId): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $tenantId = TenantContext::id();

        DB::transaction(function () use ($siteId, $tenantId): void {
            Tenant::query()->lockForUpdate()->findOrFail($tenantId);
            $site = Site::forTenant($tenantId)->whereNotNull('dispatch_region_id')->lockForUpdate()->findOrFail($siteId);
            if (DispatchTeam::forTenant($tenantId)->where('standby_site_id', $site->id)->exists()) {
                throw ValidationException::withMessages(['pointSiteId' => 'This standby point is used by a response team.']);
            }
            $site->forceFill(['dispatch_region_id' => null])->save();
        }, attempts: 3);

        session()->flash('status', 'Standby point removed from the region.');
    }

    public function createDispatchTeam(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $tenantId = TenantContext::id();
        $data = $this->validate([
            'teamName' => ['required', 'string', 'max:100', Rule::unique('dispatch_teams', 'name')->where('tenant_id', $tenantId)],
            'standbySiteId' => ['required', TenantValidation::exists('sites')->where('status', 'active')->where(fn ($query) => $query->whereNotNull('dispatch_region_id'))],
            'teamMembers' => ['required', 'array', 'min:1'],
            'teamMembers.*' => ['integer', 'distinct', TenantValidation::exists('guards')->where('status', 'active')->where('duty_type', GuardDutyType::Dispatch->value)->whereNull('dispatch_team_id')],
        ]);

        DB::transaction(function () use ($tenantId, $data): void {
            Tenant::query()->lockForUpdate()->findOrFail($tenantId);
            $standby = Site::forTenant($tenantId)->where('status', 'active')->whereNotNull('dispatch_region_id')->findOrFail($data['standbySiteId']);
            abort_unless(Branch::forTenant($tenantId)->where('is_active', true)->whereKey($standby->dispatch_region_id)->exists(), 422);

            $members = Guard::forTenant($tenantId)->whereIn('id', $data['teamMembers'])
                ->where('status', 'active')->where('duty_type', GuardDutyType::Dispatch->value)
                ->whereNull('dispatch_team_id')->lockForUpdate()->get();
            if ($members->count() !== count($data['teamMembers']) || DispatchTeam::forTenant($tenantId)->where('name', $data['teamName'])->exists()) {
                throw ValidationException::withMessages(['teamMembers' => 'The team name or selected officers are already in use. Refresh and try again.']);
            }

            $team = DispatchTeam::create(['tenant_id' => $tenantId, 'name' => $data['teamName'], 'standby_site_id' => $data['standbySiteId']]);
            Guard::forTenant($tenantId)->whereIn('id', $members->modelKeys())->update(['dispatch_team_id' => $team->id]);
        }, attempts: 3);

        $this->reset('teamName', 'standbySiteId', 'teamMembers');
        session()->flash('status', 'Dispatch team created.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'name' => '',
            'code' => '',
            'phone' => '',
            'email' => '',
            'address' => '',
            'city' => '',
            'country' => '',
            'is_active' => true,
        ];
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $regions = Branch::forTenant($tenantId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $sites = Site::forTenant($tenantId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'dispatch_region_id']);

        return view('livewire.settings.branch-index', [
            'regions' => $regions,
            'pointCandidates' => $sites->whereNull('dispatch_region_id'),
            'standbySites' => $sites->whereIn('dispatch_region_id', $regions->modelKeys()),
            'dispatchTeams' => DispatchTeam::forTenant($tenantId)->with(['standbySite', 'outstandingDispatches'])->withCount([
                'members as active_officers_count' => fn ($query) => $query->where('status', 'active')->where('duty_type', GuardDutyType::Dispatch->value),
            ])->orderBy('name')->get(),
            'unrosteredOfficers' => Guard::forTenant($tenantId)->where('status', 'active')->where('duty_type', GuardDutyType::Dispatch->value)
                ->whereNull('dispatch_team_id')->orderBy('first_name')->get(),
            'branches' => Branch::query()
                ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%')
                        ->orWhere('city', 'like', '%'.$this->search.'%');
                }))
                ->orderBy('name')
                ->paginate(20),
        ])->layout('layouts.app');
    }
}
