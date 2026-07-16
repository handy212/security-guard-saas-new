<?php

namespace App\Livewire\Patrols;

use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\FleetVehicle;
use App\Models\Site;
use App\Services\FleetService;
use App\Support\TenantContext;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class FleetIndex extends Component
{
    use AuthorizesModuleAccess, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $typeFilter = 'all';

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [
        'type' => 'car',
        'plate_number' => '',
        'name' => '',
        'make' => '',
        'model' => '',
        'site_id' => '',
        'status' => 'available',
        'current_odometer' => '',
        'notes' => '',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
        'typeFilter' => ['except' => 'all', 'as' => 'type'],
    ];

    public function mount(): void
    {
        $this->authorizePermission('patrols.manage');
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'typeFilter'], true)) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->form = [
            'type' => 'car',
            'plate_number' => '',
            'name' => '',
            'make' => '',
            'model' => '',
            'site_id' => '',
            'status' => 'available',
            'current_odometer' => '',
            'notes' => '',
        ];
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function openEdit(int $id): void
    {
        $vehicle = FleetVehicle::where('tenant_id', TenantContext::id())->findOrFail($id);
        $this->editingId = $vehicle->id;
        $this->form = [
            'type' => $vehicle->type->value,
            'plate_number' => $vehicle->plate_number,
            'name' => $vehicle->name ?? '',
            'make' => $vehicle->make ?? '',
            'model' => $vehicle->model ?? '',
            'site_id' => $vehicle->site_id ? (string) $vehicle->site_id : '',
            'status' => $vehicle->status->value,
            'current_odometer' => $vehicle->current_odometer !== null ? (string) $vehicle->current_odometer : '',
            'notes' => $vehicle->notes ?? '',
        ];
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function save(FleetService $fleet): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);

        $data = $this->validate([
            'form.type' => ['required', Rule::enum(VehicleType::class)],
            'form.plate_number' => [
                'required', 'string', 'max:40',
                Rule::unique('fleet_vehicles', 'plate_number')
                    ->where(fn ($q) => $q->where('tenant_id', TenantContext::id()))
                    ->ignore($this->editingId),
            ],
            'form.name' => 'nullable|string|max:120',
            'form.make' => 'nullable|string|max:80',
            'form.model' => 'nullable|string|max:80',
            'form.site_id' => 'nullable|exists:sites,id',
            'form.status' => ['required', Rule::enum(VehicleStatus::class)],
            'form.current_odometer' => 'nullable|integer|min:0',
            'form.notes' => 'nullable|string|max:2000',
        ])['form'];

        $payload = [
            'tenant_id' => TenantContext::id(),
            'type' => $data['type'],
            'plate_number' => strtoupper(trim($data['plate_number'])),
            'name' => $data['name'] ?: null,
            'make' => $data['make'] ?: null,
            'model' => $data['model'] ?: null,
            'site_id' => $data['site_id'] ?: null,
            'status' => $data['status'],
            'current_odometer' => $data['current_odometer'] !== '' && $data['current_odometer'] !== null
                ? (int) $data['current_odometer']
                : null,
            'notes' => $data['notes'] ?: null,
        ];

        if ($this->editingId) {
            $vehicle = FleetVehicle::where('tenant_id', TenantContext::id())->findOrFail($this->editingId);
            $fleet->update($vehicle, $payload);
            session()->flash('status', 'Vehicle updated.');
        } else {
            $fleet->create($payload);
            session()->flash('status', 'Vehicle added to fleet.');
        }

        $this->showForm = false;
        $this->editingId = null;
    }

    public function setStatus(int $id, string $status): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);
        $vehicle = FleetVehicle::where('tenant_id', TenantContext::id())->findOrFail($id);
        $vehicle->update(['status' => VehicleStatus::from($status)]);
        session()->flash('status', 'Vehicle status updated.');
    }

    public function delete(int $id, FleetService $fleet): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);
        $vehicle = FleetVehicle::where('tenant_id', TenantContext::id())->findOrFail($id);

        try {
            $fleet->delete($vehicle);
        } catch (\RuntimeException $e) {
            session()->flash('status', $e->getMessage());

            return;
        }

        session()->flash('status', 'Vehicle deleted.');
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        $vehicles = FleetVehicle::with('site')
            ->where('tenant_id', $tenantId)
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('plate_number', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('make', 'like', $term)
                        ->orWhere('model', 'like', $term);
                });
            })
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('type', $this->typeFilter))
            ->latest()
            ->paginate(20);

        return view('livewire.patrols.fleet-index', [
            'vehicles' => $vehicles,
            'sites' => Site::where('tenant_id', $tenantId)->orderBy('name')->get(),
            'types' => VehicleType::cases(),
            'statuses' => VehicleStatus::cases(),
            'stats' => [
                'total' => FleetVehicle::where('tenant_id', $tenantId)->count(),
                'available' => FleetVehicle::where('tenant_id', $tenantId)->where('status', VehicleStatus::AVAILABLE)->count(),
                'in_use' => FleetVehicle::where('tenant_id', $tenantId)->where('status', VehicleStatus::IN_USE)->count(),
                'motors' => FleetVehicle::where('tenant_id', $tenantId)->where('type', VehicleType::MOTOR)->count(),
            ],
        ])->layout('layouts.app');
    }
}
