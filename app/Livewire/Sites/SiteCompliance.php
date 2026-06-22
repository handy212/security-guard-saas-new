<?php

namespace App\Livewire\Sites;

use Livewire\Component;

class SiteCompliance extends Component
{
    public function render()
    {
        return view('livewire.sites.site-compliance', ['contacts'=>\App\Models\SiteEmergencyContact::latest()->limit(50)->get(),'documents'=>\App\Models\SiteDocument::latest()->limit(50)->get(),'sla'=>\App\Models\SiteSlaRequirement::latest()->limit(50)->get()])->layout('layouts.app');
    }
}
