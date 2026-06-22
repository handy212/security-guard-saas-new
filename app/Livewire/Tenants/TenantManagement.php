<?php

namespace App\Livewire\Tenants;

use App\Models\BillingLimit;
use App\Models\Branch;
use App\Models\SubscriptionPlan;
use Livewire\Component;

class TenantManagement extends Component
{
    public function render()
    {
        abort_unless(auth()->user()->can('tenants.manage'), 403);

        $query = fn ($model) => app('currentTenant')
            ? $model::query()
            : $model::withoutGlobalScope('tenant');

        return view('livewire.tenants.tenant-management', [
            'branches' => $query(Branch::class)->latest()->limit(20)->get(),
            'plans' => SubscriptionPlan::all(),
            'limits' => $query(BillingLimit::class)->latest()->limit(20)->get(),
        ])->layout('layouts.app');
    }
}
