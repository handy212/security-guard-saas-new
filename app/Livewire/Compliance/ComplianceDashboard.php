<?php

namespace App\Livewire\Compliance;

use App\Models\GuardCertification;
use App\Services\ComplianceService;
use App\Support\TenantContext;
use Livewire\Component;

class ComplianceDashboard extends Component
{
    public string $search = '';

    public function render()
    {
        abort_unless(auth()->user()->can('compliance.manage'), 403);
        $service = app(ComplianceService::class);
        $tenantId = TenantContext::id();

        return view('livewire.compliance.compliance-dashboard', [
            'items' => $service->expiringCertifications($tenantId),
            'documents' => $service->expiringDocuments($tenantId),
            'certifications' => GuardCertification::query()->latest()->limit(50)->get(),
        ])->layout('layouts.app');
    }
}
