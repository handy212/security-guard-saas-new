<?php

namespace App\Livewire\Tenants;

use App\Models\BillingLimit;
use App\Models\Branch;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Support\TenantContext;
use Livewire\Component;

class TenantManagement extends Component
{
    public array $branchForm = ['name' => '', 'code' => '', 'phone' => '', 'email' => '', 'address' => ''];

    public array $limitForm = ['max_guards' => 25, 'max_sites' => 10, 'max_clients' => 10, 'storage_mb' => 5120];

    public function saveBranch(): void
    {
        abort_unless(auth()->user()->can('tenants.manage'), 403);
        Branch::create($this->validate([
            'branchForm.name' => 'required',
            'branchForm.code' => 'nullable',
            'branchForm.phone' => 'nullable',
            'branchForm.email' => 'nullable|email',
            'branchForm.address' => 'nullable',
        ])['branchForm'] + ['tenant_id' => app('currentTenant')?->id ?? auth()->user()->tenant_id, 'is_active' => true]);
    }

    public function saveLimit(): void
    {
        abort_unless(auth()->user()->can('tenants.manage'), 403);
        BillingLimit::create($this->validate([
            'limitForm.max_guards' => 'integer',
            'limitForm.max_sites' => 'integer',
            'limitForm.max_clients' => 'integer',
            'limitForm.storage_mb' => 'integer',
        ])['limitForm'] + ['tenant_id' => app('currentTenant')?->id ?? auth()->user()->tenant_id]);
    }

    public function render()
    {
        abort_unless(auth()->user()->can('tenants.manage'), 403);

        $scoped = fn ($model) => app('currentTenant')
            ? $model::query()
            : $model::withoutGlobalScope('tenant');

        return view('livewire.tenants.tenant-management', [
            'tenants' => Tenant::orderBy('name')->get(),
            'branches' => $scoped(Branch::class)->latest()->limit(20)->get(),
            'plans' => SubscriptionPlan::all(),
            'limits' => $scoped(BillingLimit::class)->latest()->limit(20)->get(),
        ])->layout('layouts.app');
    }
}
