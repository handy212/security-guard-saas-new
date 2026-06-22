<?php

namespace App\Livewire\Guards;

use Livewire\Component;

class GuardHrRecords extends Component
{
    public function render()
    {
        return view('livewire.guards.guard-hr-records', ['skills'=>\App\Models\GuardSkill::latest()->limit(50)->get(),'training'=>\App\Models\TrainingRecord::latest()->limit(50)->get(),'disciplinary'=>\App\Models\DisciplinaryRecord::latest()->limit(50)->get()])->layout('layouts.app');
    }
}
