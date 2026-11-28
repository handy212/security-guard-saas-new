<?php

namespace App\Livewire\Dispatch;

use App\Enums\AssetStatus;
use App\Enums\GuardDutyType;
use App\Models\DispatchEvent;
use App\Models\DispatchTeam;
use App\Models\EquipmentAsset;
use App\Models\ResponseDeployment;
use App\Services\DispatchService;
use App\Services\ResponseDeploymentService;
use App\Support\TenantContext;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

class ResponseDeployments extends Component
{
    #[Locked]
    public string $tab = 'deployments';

    public function mount(): void
    {
        $default = auth()->user()?->can('dispatch.manage') ? 'deployments' : 'regions';
        $tab = request()->query('tab');
        $this->tab = in_array($tab, ['deployments', 'regions', 'standby', 'teams'], true) ? $tab : $default;
    }

    public ?int $teamId = null;

    public ?int $custodian = null;

    public array $officers = [];

    public string $location = '';

    public string $end = '';

    public array $kit = [];

    public array $returns = [];

    public array $arrivalReasons = [];

    public array $callHandovers = [];

    public function updatedTeamId(): void
    {
        $this->reset('officers', 'custodian', 'kit');
        $this->resetValidation();
    }

    public function addKitLine(): void
    {
        abort_unless(auth()->user()?->can('dispatch.manage'), 403);
        if (count($this->kit) < 100) {
            $this->kit[] = ['asset_id' => '', 'guard_id' => '', 'quantity' => 1, 'condition' => ''];
        }
    }

    public function removeKitLine(int $line): void
    {
        abort_unless(auth()->user()?->can('dispatch.manage'), 403);
        unset($this->kit[$line]);
        $this->resetValidation(["kit.{$line}.*", 'deployment']);
    }

    public function deploy(ResponseDeploymentService $service): void
    {
        abort_unless(auth()->user()?->can('dispatch.manage'), 403);
        $this->resetValidation('deployment');
        $data = $this->validate([
            'teamId' => ['required', 'integer'],
            'officers' => ['required', 'array', 'min:1'],
            'officers.*' => ['integer', 'distinct'],
            'custodian' => ['required', 'integer'],
            'location' => ['required', 'string', 'max:255'],
            'end' => ['required', 'date', 'after:now'],
            'kit' => ['array', 'max:100'],
            'kit.*' => ['required', 'array:asset_id,guard_id,quantity,condition'],
            'kit.*.asset_id' => ['required', 'integer'],
            'kit.*.guard_id' => ['required', 'integer'],
            'kit.*.quantity' => ['required', 'integer', 'min:1'],
            'kit.*.condition' => ['required', 'in:Good,Fair,Poor'],
        ]);
        try {
            $service->deploy(TenantContext::id(), $data['teamId'], auth()->id(), $data);
        } catch (RuntimeException $e) {
            $this->addError('deployment', $e->getMessage());

            return;
        }
        $this->reset('teamId', 'custodian', 'officers', 'kit');
        session()->flash('status', 'Team deployed. Awaiting standby arrival.');
    }

    public function confirmArrival(int $id, ResponseDeploymentService $service): void
    {
        abort_unless(auth()->user()?->can('settings.manage') && auth()->user()->can('dispatch.manage'), 403);
        $this->resetValidation('deployment');
        $this->validate(["arrivalReasons.{$id}" => ['required', 'string', 'max:255']]);
        try {
            $service->arrive(ResponseDeployment::forTenant(TenantContext::id())->findOrFail($id), auth()->id(), override: trim($this->arrivalReasons[$id]));
        } catch (RuntimeException $e) {
            $this->addError('deployment', $e->getMessage());

            return;
        }
        session()->flash('status', 'Standby arrival recorded by Control.');
    }

    public function closeDuty(int $id, ResponseDeploymentService $service): void
    {
        abort_unless(auth()->user()?->can('dispatch.manage'), 403);
        $this->resetValidation('deployment');
        $deployment = ResponseDeployment::forTenant(TenantContext::id())->findOrFail($id);
        $this->validate([
            "returns.{$id}" => ['nullable', 'array'],
            "returns.{$id}.*.outcome" => ['required', 'in:returned,damaged,missing,consumed'],
            "returns.{$id}.*.notes" => ['nullable', 'string', 'max:500'],
        ]);
        try {
            $service->close($deployment, auth()->id(), $this->returns[$id] ?? []);
        } catch (RuntimeException $e) {
            $this->addError('deployment', $e->getMessage());

            return;
        }
        unset($this->returns[$id]);
        session()->flash('status', 'Duty closed and kit reconciled.');
    }

    public function beginHandover(int $id, ResponseDeploymentService $service): void
    {
        abort_unless(auth()->user()?->can('dispatch.manage'), 403);
        $this->resetValidation('deployment');
        try {
            $service->beginHandover(ResponseDeployment::forTenant(TenantContext::id())->findOrFail($id));
        } catch (RuntimeException $e) {
            $this->addError('deployment', $e->getMessage());

            return;
        }
        session()->flash('status', 'Team taken out of dispatch availability. Receive the kit to close duty.');
    }

    public function handOverCall(int $id, DispatchService $service): void
    {
        abort_unless(auth()->user()?->can('dispatch.manage'), 403);
        $this->resetValidation('deployment');
        $data = $this->validate([
            "callHandovers.{$id}.team" => ['required', 'integer'],
            "callHandovers.{$id}.reason" => ['required', 'string', 'max:500'],
        ])['callHandovers'][$id];
        $service->handOverTeamCall(DispatchEvent::forTenant(TenantContext::id())->findOrFail($id), (int) $data['team'], auth()->id(), $data['reason']);
        unset($this->callHandovers[$id]);
        session()->flash('status', 'Call handed over to the receiving team.');
    }

    public function render()
    {
        if ($this->tab !== 'deployments') {
            abort_unless(auth()->user()?->can('settings.manage'), 403);

            return view('livewire.dispatch.response-deployments')->layout('layouts.app');
        }

        abort_unless(auth()->user()?->can('dispatch.manage'), 403);
        $tenantId = TenantContext::id();
        $active = ResponseDeployment::forTenant($tenantId)->whereNull('closed_at')
            ->with(['team.outstandingDispatches', 'custodian', 'kit.asset.assetCategory', 'kit.assignedGuard'])->latest()->get();

        return view('livewire.dispatch.response-deployments', [
            'teams' => DispatchTeam::forTenant($tenantId)->with('standbyPoint.region')
                ->doesntHave('activeDeployment')->doesntHave('outstandingDispatches')->orderBy('name')->get(),
            'teamOfficers' => DispatchTeam::forTenant($tenantId)->find($this->teamId)?->members()
                ->where('status', 'active')->where('duty_type', GuardDutyType::Dispatch->value)->get() ?? collect(),
            'assets' => EquipmentAsset::forTenant($tenantId)->where('status', AssetStatus::AVAILABLE)->with('assetCategory')->orderBy('name')->get(),
            'active' => $active,
            'ready' => $active->filter(fn (ResponseDeployment $deployment): bool => $deployment->arrived_at
                && ! $deployment->ending_at && $deployment->team->outstandingDispatches->isEmpty()),
            'history' => ResponseDeployment::forTenant($tenantId)->whereNotNull('closed_at')->with(['team', 'custodian', 'kit.asset', 'kit.assignedGuard'])->latest('closed_at')->limit(15)->get(),
        ])->layout('layouts.app');
    }
}
