<?php

namespace App\Livewire\Settings;

use App\Enums\GuardDutyType;
use App\Livewire\Concerns\HasFormDrawer;
use App\Models\Branch;
use App\Models\DispatchStandbyPoint;
use App\Models\DispatchTeam;
use App\Models\Guard;
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

    public ?int $standbyPointId = null;

    public array $teamStandbyPoints = [];

    public array $teamMembers = [];

    public ?int $pointRegionId = null;

    public ?int $editingStandbyPointId = null;

    public string $pointName = '';

    public string $pointLatitude = '';

    public string $pointLongitude = '';

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

    public function createStandbyPoint(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $data = $this->validate([
            'pointRegionId' => ['required', TenantValidation::exists('branches')->where('is_active', true)],
            'pointName' => ['required', 'string', 'max:255'],
            'pointLatitude' => ['required', 'numeric', 'between:-90,90'],
            'pointLongitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $attributes = [
            'region_id' => $data['pointRegionId'],
            'name' => $data['pointName'],
            'latitude' => $data['pointLatitude'],
            'longitude' => $data['pointLongitude'],
        ];

        if ($this->editingStandbyPointId) {
            DispatchStandbyPoint::forTenant(TenantContext::id())
                ->findOrFail($this->editingStandbyPointId)
                ->update($attributes);
            $message = 'Standby point updated.';
        } else {
            DispatchStandbyPoint::create($attributes + ['tenant_id' => TenantContext::id()]);
            $message = 'Standby point created.';
        }

        $this->resetStandbyPointForm();
        session()->flash('status', $message);
    }

    public function editStandbyPoint(int $pointId): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $point = DispatchStandbyPoint::forTenant(TenantContext::id())->findOrFail($pointId);

        $this->editingStandbyPointId = $point->id;
        $this->pointRegionId = $point->region_id;
        $this->pointName = $point->name;
        $this->pointLatitude = (string) $point->latitude;
        $this->pointLongitude = (string) $point->longitude;
        $this->resetValidation();
    }

    public function cancelStandbyPointEdit(): void
    {
        $this->resetStandbyPointForm();
    }

    private function resetStandbyPointForm(): void
    {
        $this->reset('editingStandbyPointId', 'pointRegionId', 'pointName', 'pointLatitude', 'pointLongitude');
        $this->resetValidation();
    }

    public function removeStandbyPoint(int $pointId): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $tenantId = TenantContext::id();

        DB::transaction(function () use ($pointId, $tenantId): void {
            Tenant::query()->lockForUpdate()->findOrFail($tenantId);
            $point = DispatchStandbyPoint::forTenant($tenantId)->findOrFail($pointId);
            if (DispatchTeam::forTenant($tenantId)->where('standby_point_id', $point->id)->exists()) {
                throw ValidationException::withMessages(['standbyPoint' => 'Reassign the response teams using this point before removing it.']);
            }
            $point->delete();
        }, attempts: 3);

        session()->flash('status', 'Standby point removed.');
    }

    public function assignTeamStandbyPoint(int $teamId): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $tenantId = TenantContext::id();
        $field = 'teamStandbyPoints.'.$teamId;
        $this->validate([$field => ['required', 'integer', TenantValidation::exists('dispatch_standby_points')]]);

        DB::transaction(function () use ($teamId, $tenantId, $field): void {
            Tenant::query()->lockForUpdate()->findOrFail($tenantId);
            $team = DispatchTeam::forTenant($tenantId)->lockForUpdate()->findOrFail($teamId);
            if ($team->outstandingDispatches()->exists()) {
                throw ValidationException::withMessages([$field => 'Wait until the team confirms its return before changing its standby point.']);
            }
            $point = $this->availableStandbyPoint((int) $this->teamStandbyPoints[$teamId], $field);
            $team->update(['standby_point_id' => $point->id]);
        }, attempts: 3);

        unset($this->teamStandbyPoints[$teamId]);
        session()->flash('status', 'Team standby point updated.');
    }

    private function availableStandbyPoint(int $pointId, string $field): DispatchStandbyPoint
    {
        $point = DispatchStandbyPoint::forTenant(TenantContext::id())
            ->whereHas('region', fn ($query) => $query->where('is_active', true))->find($pointId);
        if (! $point) {
            throw ValidationException::withMessages([$field => 'Select a standby point in an active region.']);
        }

        return $point;
    }

    public function createDispatchTeam(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $tenantId = TenantContext::id();
        $data = $this->validate([
            'teamName' => ['required', 'string', 'max:100', Rule::unique('dispatch_teams', 'name')->where('tenant_id', $tenantId)],
            'standbyPointId' => ['required', TenantValidation::exists('dispatch_standby_points')],
            'teamMembers' => ['required', 'array', 'min:1'],
            'teamMembers.*' => ['integer', 'distinct', TenantValidation::exists('guards')->where('status', 'active')->where('duty_type', GuardDutyType::Dispatch->value)->whereNull('dispatch_team_id')],
        ]);

        DB::transaction(function () use ($tenantId, $data): void {
            Tenant::query()->lockForUpdate()->findOrFail($tenantId);
            $standby = $this->availableStandbyPoint($data['standbyPointId'], 'standbyPointId');

            $members = Guard::forTenant($tenantId)->whereIn('id', $data['teamMembers'])
                ->where('status', 'active')->where('duty_type', GuardDutyType::Dispatch->value)
                ->whereNull('dispatch_team_id')->lockForUpdate()->get();
            if ($members->count() !== count($data['teamMembers']) || DispatchTeam::forTenant($tenantId)->where('name', $data['teamName'])->exists()) {
                throw ValidationException::withMessages(['teamMembers' => 'The team name or selected officers are already in use. Refresh and try again.']);
            }

            $team = DispatchTeam::create(['tenant_id' => $tenantId, 'name' => $data['teamName'], 'standby_point_id' => $standby->id]);
            Guard::forTenant($tenantId)->whereIn('id', $members->modelKeys())->update(['dispatch_team_id' => $team->id]);
        }, attempts: 3);

        $this->reset('teamName', 'standbyPointId', 'teamMembers');
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
        $points = DispatchStandbyPoint::forTenant($tenantId)->with('region')->orderBy('name')->get();

        return view('livewire.settings.branch-index', [
            'regions' => $regions,
            'standbyPoints' => $points,
            'availablePoints' => $points->whereIn('region_id', $regions->modelKeys()),
            'dispatchTeams' => DispatchTeam::forTenant($tenantId)->with(['standbyPoint.region', 'outstandingDispatches'])->withCount([
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
