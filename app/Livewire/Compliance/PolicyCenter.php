<?php

namespace App\Livewire\Compliance;

use Livewire\Component;

class PolicyCenter extends Component
{
    public function render()
    {
        return view('livewire.compliance.policy-center', ['escalations'=>\App\Models\IncidentEscalationRule::latest()->get(),'retention'=>\App\Models\DataRetentionPolicy::latest()->get(),'sla'=>\App\Models\SiteSlaRequirement::latest()->get()])->layout('layouts.app');
    }
}
