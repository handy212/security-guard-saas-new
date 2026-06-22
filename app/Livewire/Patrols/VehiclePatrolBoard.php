<?php

namespace App\Livewire\Patrols;

use App\Models\VehiclePatrol;
use App\Support\TenantContext;
use Livewire\Component;

class VehiclePatrolBoard extends Component
{
    public array $form = ['vehicle_number' => '', 'driver_name' => '', 'start_odometer' => '', 'end_odometer' => ''];

    public function save(): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);
        VehiclePatrol::create($this->validate([
            'form.vehicle_number' => 'required',
            'form.driver_name' => 'nullable',
            'form.start_odometer' => 'nullable|integer',
            'form.end_odometer' => 'nullable|integer',
        ])['form'] + ['tenant_id' => TenantContext::id()]);
    }

    public function render()
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);

        return view('livewire.patrols.vehicle-patrol-board', [
            'vehiclePatrols' => VehiclePatrol::latest()->limit(50)->get(),
        ])->layout('layouts.app');
    }
}
