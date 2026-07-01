<?php

namespace App\Livewire\Compliance;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\GuardCertification;
use App\Services\ComplianceService;
use App\Support\TenantContext;
use Livewire\Component;

class ComplianceDashboard extends Component
{
    use AuthorizesModuleAccess;

    public string $search = '';

    public function mount(): void
    {
        $this->authorizePermission('compliance.manage');
    }

    public function render()
    {
        $service = app(ComplianceService::class);
        $tenantId = TenantContext::id();

        return view('livewire.compliance.compliance-dashboard', [
            'items' => $service->expiringCertifications($tenantId),
            'documents' => $service->expiringDocuments($tenantId),
            'certifications' => GuardCertification::query()->latest()->limit(50)->get(),
        ])->layout('layouts.app');
    }
}
