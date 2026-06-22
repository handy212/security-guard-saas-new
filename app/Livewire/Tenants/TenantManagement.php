<?php

namespace App\Livewire\Tenants;

use Livewire\Component;

class TenantManagement extends Component
{
    public function render()
    {
        return view('livewire.tenants.tenant-management', ['branches'=>\App\Models\Branch::latest()->limit(20)->get(),'plans'=>\App\Models\SubscriptionPlan::all(),'limits'=>\App\Models\BillingLimit::latest()->limit(20)->get()])->layout('layouts.app');
    }
}
