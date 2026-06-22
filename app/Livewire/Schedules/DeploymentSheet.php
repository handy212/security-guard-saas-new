<?php

namespace App\Livewire\Schedules;

use Livewire\Component;

class DeploymentSheet extends Component
{
    public function render()
    {
        return view('livewire.schedules.deployment-sheet', ['assignments'=>\App\Models\ShiftAssignment::with(['shift.site','guard'])->whereDate('created_at',today())->latest()->get()])->layout('layouts.app');
    }
}
