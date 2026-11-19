<?php

namespace App\Livewire\Patrols;

use App\Livewire\Concerns\HasFormDrawer;
use App\Models\Guard;
use App\Models\JwmCheckpointMapping;
use App\Models\JwmDevice;
use App\Models\JwmDeviceAssignment;
use App\Models\JwmGuardMapping;
use App\Models\PatrolCheckpoint;
use App\Services\JwmDeviceManagementService;
use App\Services\PlanEntitlementService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class JwmDeviceManager extends Component
{
    use HasFormDrawer, WithPagination;

    #[Url(as: 'reader')]
    public ?int $deviceId = null;

    #[Locked]
    public string $operation = 'register';

    #[Locked]
    public ?int $editingDeviceId = null;

    public string $search = '';

    public string $targetSearch = '';

    public string $tab = 'handover';

    public array $form = [];

    public function mount(): void
    {
        $this->authorizeManagement();
    }

    public function selectDevice(int $id): void
    {
        $this->authorizeManagement();
        JwmDevice::where('tenant_id', TenantContext::id())->findOrFail($id);
        $this->deviceId = $id;
        $this->closeDrawer();
        $this->resetPage('entries');
    }

    public function openCreate(): void
    {
        $this->authorizeManagement();
        $this->resetValidation();
        $this->operation = 'register';
        $this->editingDeviceId = null;
        $this->form = ['name' => '', 'reader_number' => ''];
        $this->showForm = true;
    }

    public function openEditor(string $operation): void
    {
        $this->authorizeManagement();
        abort_unless(in_array($operation, ['settings', 'handover', 'guard', 'checkpoint'], true), 404);
        $device = JwmDevice::where('tenant_id', TenantContext::id())->findOrFail($this->deviceId);
        $this->resetValidation();
        $this->targetSearch = '';
        $this->operation = $operation;
        $this->editingDeviceId = $device->id;
        $this->form = match ($operation) {
            'settings' => $device->only(['is_active', 'require_device_identity']),
            'handover' => ['guard_id' => '', 'starts_at' => '', 'ends_at' => ''],
            'guard' => ['external_id' => '', 'guard_id' => ''],
            'checkpoint' => ['external_id' => '', 'patrol_checkpoint_id' => ''],
        };
        $this->showForm = true;
    }

    public function save(JwmDeviceManagementService $management): void
    {
        $this->authorizeManagement();
        abort_unless($this->schemaReady(), 503, 'JWM database setup is required before saving.');
        $tenantId = TenantContext::id();
        if ($this->operation !== 'register') {
            abort_unless($this->deviceId === $this->editingDeviceId, 409, 'Reader selection changed. Reopen the form before saving.');
            JwmDevice::where('tenant_id', $tenantId)->findOrFail($this->deviceId);
        }
        try {
            $result = match ($this->operation) {
                'register' => $management->register($tenantId, $this->form),
                'settings' => $management->configure($tenantId, $this->deviceId, $this->form),
                'handover' => $management->assign($tenantId, $this->deviceId, $this->form),
                'guard' => $management->mapGuard($tenantId, $this->deviceId, $this->form),
                'checkpoint' => $management->mapCheckpoint($tenantId, $this->deviceId, $this->form),
            };
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(collect($exception->errors())
                ->mapWithKeys(fn ($messages, $field) => ['form.'.$field => $messages])->all());
        }
        if ($this->operation === 'register') {
            $this->deviceId = $result->id;
            $this->search = '';
            $this->resetPage('readers');
        } elseif ($this->operation !== 'settings') {
            $this->tab = $this->operation;
        }
        $this->resetPage('entries');
        $this->closeDrawer();
        session()->flash('status', 'Reader configuration saved. No command was sent to the device.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage('readers');
    }

    public function updatedTab(): void
    {
        $this->resetPage('entries');
    }

    public function updatedTargetSearch(): void
    {
        unset($this->form['guard_id'], $this->form['patrol_checkpoint_id']);
    }

    private function authorizeManagement(): void
    {
        abort_unless(auth()->user()?->can('patrols.manage'), 403);
        $tenantId = TenantContext::id();
        abort_unless(app(PlanEntitlementService::class)->tenantHasFeature($tenantId, 'patrols'), 403);
    }

    private function schemaReady(): bool
    {
        return Schema::hasTable('jwm_devices') && Schema::hasTable('jwm_device_assignments')
            && Schema::hasTable('jwm_guard_mappings') && Schema::hasTable('jwm_checkpoint_mappings')
            && Schema::hasColumn('jwm_devices', 'require_device_identity');
    }

    public function render()
    {
        $this->authorizeManagement();
        $ready = $this->schemaReady();
        $tenantId = TenantContext::id();
        $device = $ready && $this->deviceId !== null
            ? JwmDevice::where('tenant_id', $tenantId)->findOrFail($this->deviceId) : null;
        $readers = $ready ? JwmDevice::where('tenant_id', $tenantId)
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$this->search.'%')->orWhere('reader_number', 'like', '%'.$this->search.'%')))
            ->latest('id')->paginate(10, ['*'], 'readers') : null;
        $tab = in_array($this->tab, ['handover', 'guard', 'checkpoint'], true) ? $this->tab : 'handover';
        $entries = $device ? match ($tab) {
            'handover' => JwmDeviceAssignment::where('tenant_id', $tenantId)->where('jwm_device_id', $device->id)
                ->with(['assignedGuard' => fn ($q) => $q->where('tenant_id', $tenantId)])->latest('starts_at')->paginate(10, ['*'], 'entries'),
            'guard' => JwmGuardMapping::where('tenant_id', $tenantId)->where('jwm_device_id', $device->id)
                ->with(['assignedGuard' => fn ($q) => $q->where('tenant_id', $tenantId)])->latest('id')->paginate(10, ['*'], 'entries'),
            'checkpoint' => JwmCheckpointMapping::where('tenant_id', $tenantId)->where('jwm_device_id', $device->id)
                ->with(['checkpoint' => fn ($q) => $q->where('tenant_id', $tenantId)])->latest('id')->paginate(10, ['*'], 'entries'),
        } : null;
        $guards = collect();
        $checkpoints = collect();
        if ($this->showForm && in_array($this->operation, ['handover', 'guard'], true)) {
            $guards = Guard::where('tenant_id', $tenantId)
                ->when($this->targetSearch !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->where('first_name', 'like', '%'.$this->targetSearch.'%')->orWhere('last_name', 'like', '%'.$this->targetSearch.'%')
                    ->orWhere('employee_number', 'like', '%'.$this->targetSearch.'%')))
                ->orderBy('first_name')->orderBy('id')->limit(50)->get();
        } elseif ($this->showForm && $this->operation === 'checkpoint') {
            $checkpoints = PatrolCheckpoint::where('tenant_id', $tenantId)
                ->when($this->targetSearch !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->where('name', 'like', '%'.$this->targetSearch.'%')->orWhere('code', 'like', '%'.$this->targetSearch.'%')))
                ->orderBy('name')->orderBy('id')->limit(50)->get();
        }

        return view('livewire.patrols.jwm-device-manager', compact(
            'ready', 'device', 'readers', 'entries', 'tab', 'guards', 'checkpoints',
        ))->layout('layouts.app');
    }
}
