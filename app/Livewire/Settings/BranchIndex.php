<?php

namespace App\Livewire\Settings;

use App\Enums\GuardDutyType;
use App\Livewire\Concerns\HasFormDrawer;
use App\Models\Branch;
use App\Models\DispatchStandbyPoint;
use App\Models\DispatchTeam;
use App\Models\Guard;
use App\Models\Tenant;
use App\Services\ReverseGeocodingService;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class BranchIndex extends Component
{
    use HasFormDrawer, WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public string $teamName = '';

    public ?int $editingTeamId = null;

    public ?int $standbyPointId = null;

    public array $teamStandbyPoints = [];

    public array $teamMembers = [];

    public ?int $pointRegionId = null;

    public ?int $editingStandbyPointId = null;

    public string $pointName = '';

    public string $pointLatitude = '';

    public string $pointLongitude = '';

    public string $pointAddress = '';

    public string $pointRadius = '';

    public function lookupStandbyAddress(ReverseGeocodingService $geocoder): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $data = $this->validate($this->standbyCoordinateRules());
        $address = $geocoder->address((float) $data['pointLatitude'], (float) $data['pointLongitude']);
        if ($address === null) {
            $this->addError('pointAddress', 'Address lookup unavailable. You can enter an address manually.');

            return;
        }
        $this->pointAddress = $address;
        $this->resetValidation('pointAddress');
    }

    private function standbyCoordinateRules(): array
    {
        return [
            'pointLatitude' => ['required', 'numeric', 'between:-90,90'],
            'pointLongitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

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

    #[Locked]
    public string $section = 'regions';

    public function mount(string $section = 'regions'): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $this->section = in_array($section, ['regions', 'standby', 'teams'], true) ? $section : 'regions';
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
        $tenantId = TenantContext::id();
        $branch = $this->editingId ? Branch::forTenant($tenantId)->findOrFail($this->editingId) : null;
        $uniqueCode = Rule::unique('branches', 'code')->where('tenant_id', $tenantId);
        if ($branch) {
            $uniqueCode->ignore($branch);
        }
        $this->form['code'] = is_string($this->form['code']) && trim($this->form['code']) === '' ? null : $this->form['code'];

        $data = $this->validate([
            'form.name' => 'required|string|max:255',
            'form.code' => ['nullable', 'string', 'max:50', $uniqueCode],
            'form.phone' => 'nullable|string|max:50',
            'form.email' => 'nullable|email|max:255',
            'form.address' => 'nullable|string|max:500',
            'form.city' => 'nullable|string|max:120',
            'form.country' => 'nullable|string|max:120',
            'form.is_active' => 'boolean',
        ])['form'];

        if ($branch) {
            $branch->update($data);
        } else {
            Branch::create($data + ['tenant_id' => $tenantId]);
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
            'pointAddress' => ['nullable', 'string', 'max:500'],
            'pointRadius' => ['nullable', 'integer', 'between:1,100000'],
            ...$this->standbyCoordinateRules(),
        ]);

        $attributes = [
            'region_id' => $data['pointRegionId'],
            'name' => $data['pointName'],
            'address' => trim($data['pointAddress'] ?? '') ?: null,
            'radius_meters' => filled($data['pointRadius'] ?? null) ? (int) $data['pointRadius'] : null,
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
        $this->pointAddress = $point->address ?? '';
        $this->pointRadius = (string) $point->radius_meters;
        $this->resetValidation();
    }

    public function cancelStandbyPointEdit(): void
    {
        $this->resetStandbyPointForm();
    }

    private function resetStandbyPointForm(): void
    {
        $this->reset('editingStandbyPointId', 'pointRegionId', 'pointName', 'pointLatitude', 'pointLongitude', 'pointAddress', 'pointRadius');
        $this->resetValidation();
    }

    public function removeStandbyPoint(int $pointId): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $tenantId = TenantContext::id();

        DB::transaction(function () use ($pointId, $tenantId): void {
            Tenant::query()->lockForUpdate()->findOrFail($tenantId);
            $point = DispatchStandbyPoint::forTenant($tenantId)->findOrFail($pointId);
            $teams = DispatchTeam::forTenant($tenantId)->where('standby_point_id', $point->id)->lockForUpdate()->get();
            if ($teams->isNotEmpty() && DispatchTeam::forTenant($tenantId)->whereKey($teams->modelKeys())
                ->where(fn ($query) => $query->has('outstandingDispatches')->orHas('activeDeployment'))->exists()) {
                throw ValidationException::withMessages(['standbyPoint' => 'Close all team deployments and outstanding calls before removing this point.']);
            }
            $point->delete();
        }, attempts: 3);

        if ($this->editingStandbyPointId === $pointId) {
            $this->resetStandbyPointForm();
        }
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
            if ($team->outstandingDispatches()->exists() || $team->activeDeployment()->exists()) {
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

    public function editDispatchTeam(int $teamId): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $team = DispatchTeam::forTenant(TenantContext::id())->findOrFail($teamId);
        $this->ensureTeamIsIdle($team);
        $this->editingTeamId = $team->id;
        $this->teamName = $team->name;
        $this->standbyPointId = $team->standby_point_id;
        $this->teamMembers = $team->members()->where('status', 'active')
            ->where('duty_type', GuardDutyType::Dispatch->value)->pluck('id')->map(fn ($id): string => (string) $id)->all();
        $this->resetValidation();
    }

    public function cancelTeamEdit(): void
    {
        $this->reset('editingTeamId', 'teamName', 'standbyPointId', 'teamMembers');
        $this->resetValidation();
    }

    private function ensureTeamIsIdle(DispatchTeam $team): void
    {
        if ($team->outstandingDispatches()->exists() || $team->activeDeployment()->exists()) {
            throw ValidationException::withMessages(['responseTeam' => 'Close the team’s active deployment and outstanding calls before editing or removing it.']);
        }
    }

    public function removeDispatchTeam(int $teamId): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $tenantId = TenantContext::id();
        DB::transaction(function () use ($tenantId, $teamId): void {
            Tenant::query()->lockForUpdate()->findOrFail($tenantId);
            $team = DispatchTeam::forTenant($tenantId)->lockForUpdate()->findOrFail($teamId);
            $this->ensureTeamIsIdle($team);
            Guard::forTenant($tenantId)->where('dispatch_team_id', $team->id)->update(['dispatch_team_id' => null]);
            $team->delete();
        }, attempts: 3);

        if ($this->editingTeamId === $teamId) {
            $this->cancelTeamEdit();
        }
        session()->flash('status', 'Response team removed.');
    }

    public function createDispatchTeam(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $tenantId = TenantContext::id();
        $editingTeam = $this->editingTeamId !== null ? DispatchTeam::forTenant($tenantId)->findOrFail($this->editingTeamId) : null;
        $uniqueName = Rule::unique('dispatch_teams', 'name')->where('tenant_id', $tenantId);
        if ($editingTeam) {
            $uniqueName->ignore($editingTeam);
        }
        $data = $this->validate([
            'teamName' => ['required', 'string', 'max:100', $uniqueName],
            'standbyPointId' => [$editingTeam ? 'nullable' : 'required', TenantValidation::exists('dispatch_standby_points')],
            'teamMembers' => ['required', 'array', 'min:1'],
            'teamMembers.*' => ['integer', 'distinct', TenantValidation::exists('guards')->where('status', 'active')->where('duty_type', GuardDutyType::Dispatch->value)],
        ]);

        DB::transaction(function () use ($tenantId, $data, $editingTeam): void {
            Tenant::query()->lockForUpdate()->findOrFail($tenantId);
            $team = $editingTeam ? DispatchTeam::forTenant($tenantId)->lockForUpdate()->findOrFail($editingTeam->id) : null;
            if ($team) {
                $this->ensureTeamIsIdle($team);
            }
            $standby = filled($data['standbyPointId'] ?? null) ? $this->availableStandbyPoint($data['standbyPointId'], 'standbyPointId') : null;

            $members = Guard::forTenant($tenantId)->whereIn('id', $data['teamMembers'])
                ->where('status', 'active')->where('duty_type', GuardDutyType::Dispatch->value)
                ->where(fn ($query) => $query->whereNull('dispatch_team_id')->when($team, fn ($query) => $query->orWhere('dispatch_team_id', $team->id)))
                ->lockForUpdate()->get();
            $nameTaken = DispatchTeam::withTrashed()->forTenant($tenantId)->where('name', $data['teamName'])
                ->when($team, fn ($query) => $query->whereKeyNot($team->id))->exists();
            if ($members->count() !== count($data['teamMembers']) || $nameTaken) {
                throw ValidationException::withMessages(['teamMembers' => 'The team name or selected officers are already in use. Refresh and try again.']);
            }

            $attributes = ['name' => $data['teamName'], 'standby_point_id' => $standby?->id];
            if ($team) {
                $team->update($attributes);
                Guard::forTenant($tenantId)->where('dispatch_team_id', $team->id)
                    ->whereNotIn('id', $members->modelKeys())->update(['dispatch_team_id' => null]);
            } else {
                $team = DispatchTeam::create($attributes + ['tenant_id' => $tenantId]);
            }
            Guard::forTenant($tenantId)->whereIn('id', $members->modelKeys())->update(['dispatch_team_id' => $team->id]);
        }, attempts: 3);

        $this->cancelTeamEdit();
        session()->flash('status', $editingTeam ? 'Response team updated.' : 'Dispatch team created.');
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
        abort_unless(auth()->user()?->can('settings.manage'), 403);
        $tenantId = TenantContext::id();
        $regions = Branch::forTenant($tenantId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $points = DispatchStandbyPoint::forTenant($tenantId)->with('region')->orderBy('name')->get();

        return view('livewire.settings.branch-index', [
            'regions' => $regions,
            'standbyPoints' => $points,
            'availablePoints' => $points->whereIn('region_id', $regions->modelKeys()),
            'dispatchTeams' => DispatchTeam::forTenant($tenantId)->with(['standbyPoint.region', 'outstandingDispatches', 'activeDeployment'])->withCount([
                'members as active_officers_count' => fn ($query) => $query->where('status', 'active')->where('duty_type', GuardDutyType::Dispatch->value),
            ])->orderBy('name')->get(),
            'unrosteredOfficers' => Guard::forTenant($tenantId)->where('status', 'active')->where('duty_type', GuardDutyType::Dispatch->value)
                ->where(fn ($query) => $query->whereNull('dispatch_team_id')->when($this->editingTeamId, fn ($query) => $query->orWhere('dispatch_team_id', $this->editingTeamId)))
                ->orderBy('first_name')->get(),
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
