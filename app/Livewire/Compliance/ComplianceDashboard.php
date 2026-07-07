<?php

namespace App\Livewire\Compliance;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Services\ComplianceService;
use App\Support\TenantContext;
use Livewire\Component;

class ComplianceDashboard extends Component
{
    use AuthorizesModuleAccess;

    public int $windowDays = 30;

    public function mount(): void
    {
        $this->authorizePermission('compliance.manage');
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $service = app(ComplianceService::class);

        return view('livewire.compliance.compliance-dashboard', [
            'summary' => $service->summary($tenantId, $this->windowDays),
            'certifications' => $service->expiringCertifications($tenantId, $this->windowDays),
            'documents' => $service->expiringDocuments($tenantId, $this->windowDays),
            'siteDocuments' => $service->expiringSiteDocuments($tenantId, $this->windowDays),
            'training' => $service->expiringTraining($tenantId, $this->windowDays),
        ])->layout('layouts.app');
    }
}
