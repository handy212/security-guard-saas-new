<?php

namespace App\Livewire\Patrols;

use Livewire\Component;

class VehiclePatrolBoard extends Component
{
    public function render()
    {
        return view('livewire.patrols.vehicle-patrol-board', ['vehiclePatrols'=>\App\Models\VehiclePatrol::latest()->limit(50)->get()])->layout('layouts.app');
    }
}
