<?php

namespace App\Livewire\Patrols;

use App\Enums\VehicleStatus;
use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\FleetVehicle;
use App\Models\Guard;
use App\Models\PatrolSession;
use App\Models\VehiclePatrol;
use App\Services\FleetService;
use App\Support\TenantContext;
use Livewire\Component;
use RuntimeException;

class VehiclePatrolBoard extends Component
{
    use AuthorizesModuleAccess;

    public bool $showStartForm = false;

    public array $form = [
        'vehicle_id' => '',
        'guard_id' => '',
        'patrol_session_id' => '',
        'start_odometer' => '',
        'fuel_litres' => '',
        'fuel_cost' => '',
    ];

    public ?int $endingId = null;

    public array $endForm = [
        'end_odometer' => '',
        'fuel_litres' => '',
        'fuel_cost' => '',
        'patrol_session_id' => '',
    ];

    public function mount(): void
    {
        $this->authorizePermission('patrols.manage');
    }

    public function openStartForm(): void
    {
        $this->endingId = null;
        $this->form = [
            'vehicle_id' => '',
            'guard_id' => '',
            'patrol_session_id' => '',
            'start_odometer' => '',
            'fuel_litres' => '',
            'fuel_cost' => '',
        ];
        $this->resetErrorBag();
        $this->showStartForm = true;
    }

    public function closeStartForm(): void
    {
        $this->showStartForm = false;
        $this->resetErrorBag();
    }

    public function startTrip(FleetService $fleet): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);

        $data = $this->validate([
            'form.vehicle_id' => 'required|exists:fleet_vehicles,id',
            'form.guard_id' => 'nullable|exists:guards,id',
            'form.patrol_session_id' => 'nullable|exists:patrol_sessions,id',
            'form.start_odometer' => 'nullable|integer|min:0',
            'form.fuel_litres' => 'nullable|numeric|min:0',
            'form.fuel_cost' => 'nullable|numeric|min:0',
        ])['form'];

        try {
            $fleet->startTrip([
                'vehicle_id' => (int) $data['vehicle_id'],
                'guard_id' => $data['guard_id'] ?: null,
                'patrol_session_id' => $data['patrol_session_id'] ?: null,
                'start_odometer' => $data['start_odometer'] !== '' ? (int) $data['start_odometer'] : null,
                'fuel_litres' => $data['fuel_litres'],
                'fuel_cost' => $data['fuel_cost'],
            ]);
        } catch (RuntimeException $e) {
            $this->addError('form.vehicle_id', $e->getMessage());

            return;
        }

        $this->form = [
            'vehicle_id' => '',
            'guard_id' => '',
            'patrol_session_id' => '',
            'start_odometer' => '',
            'fuel_litres' => '',
            'fuel_cost' => '',
        ];
        $this->showStartForm = false;

        session()->flash('status', 'Vehicle assigned to patrol trip.');
    }

    public function openEnd(int $id): void
    {
        $trip = VehiclePatrol::where('tenant_id', TenantContext::id())->findOrFail($id);
        $this->showStartForm = false;
        $this->endingId = $trip->id;
        $this->endForm = [
            'end_odometer' => $trip->start_odometer !== null ? (string) $trip->start_odometer : '',
            'fuel_litres' => '',
            'fuel_cost' => '',
            'patrol_session_id' => $trip->patrol_session_id ? (string) $trip->patrol_session_id : '',
        ];
        $this->resetErrorBag();
    }

    public function closeEndForm(): void
    {
        $this->endingId = null;
        $this->resetErrorBag();
    }

    public function endTrip(FleetService $fleet): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);
        $trip = VehiclePatrol::where('tenant_id', TenantContext::id())->findOrFail($this->endingId);

        $data = $this->validate([
            'endForm.end_odometer' => 'required|integer|min:0',
            'endForm.fuel_litres' => 'nullable|numeric|min:0',
            'endForm.fuel_cost' => 'nullable|numeric|min:0',
            'endForm.patrol_session_id' => 'nullable|exists:patrol_sessions,id',
        ])['endForm'];

        try {
            $fleet->endTrip($trip, [
                'end_odometer' => (int) $data['end_odometer'],
                'fuel_litres' => $data['fuel_litres'],
                'fuel_cost' => $data['fuel_cost'],
                'patrol_session_id' => $data['patrol_session_id'] ?: null,
            ]);
        } catch (RuntimeException $e) {
            $this->addError('endForm.end_odometer', $e->getMessage());

            return;
        }

        $this->endingId = null;
        session()->flash('status', 'Trip completed. Vehicle marked available.');
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        $vehiclePatrols = VehiclePatrol::with(['vehicle', 'assignedGuard', 'patrolSession.route'])
            ->where('tenant_id', $tenantId)
            ->latest()
            ->limit(50)
            ->get();

        $fleet = FleetVehicle::where('tenant_id', $tenantId)
            ->whereIn('status', [VehicleStatus::AVAILABLE, VehicleStatus::IN_USE])
            ->orderBy('plate_number')
            ->get();

        return view('livewire.patrols.vehicle-patrol-board', [
            'vehiclePatrols' => $vehiclePatrols,
            'sessions' => PatrolSession::with('route')
                ->where('tenant_id', $tenantId)
                ->where('status', 'in_progress')
                ->latest()
                ->limit(40)
                ->get(),
            'fleet' => $fleet,
            'availableFleet' => $fleet->filter(fn (FleetVehicle $v) => $v->status === VehicleStatus::AVAILABLE)->values(),
            'guards' => Guard::where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->orderBy('first_name')
                ->get(),
            'endingId' => $this->endingId,
        ])->layout('layouts.app');
    }
}
