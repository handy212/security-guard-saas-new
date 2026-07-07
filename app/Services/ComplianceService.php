<?php

namespace App\Services;

use App\Models\GuardCertification;
use App\Models\GuardDocument;
use App\Models\Site;
use App\Models\SiteDocument;
use App\Models\SiteSlaRequirement;
use App\Models\TrainingRecord;
use Illuminate\Support\Collection;

class ComplianceService
{
    public function expiringCertifications(int $tenantId, int $days = 30): Collection
    {
        return GuardCertification::query()
            ->with('assignedGuard')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', now()->addDays($days))
            ->orderBy('expires_at')
            ->get();
    }

    public function expiringDocuments(int $tenantId, int $days = 30): Collection
    {
        return GuardDocument::query()
            ->with('assignedGuard')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', now()->addDays($days))
            ->orderBy('expires_at')
            ->get();
    }

    public function expiringSiteDocuments(int $tenantId, int $days = 30): Collection
    {
        return SiteDocument::query()
            ->with('site')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('expires_on')
            ->whereDate('expires_on', '<=', now()->addDays($days))
            ->orderBy('expires_on')
            ->get();
    }

    public function expiringTraining(int $tenantId, int $days = 30): Collection
    {
        return TrainingRecord::query()
            ->with('assignedGuard')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('expires_on')
            ->whereDate('expires_on', '<=', now()->addDays($days))
            ->orderBy('expires_on')
            ->get();
    }

    public function expiringWithinDays(int $tenantId, int $days = 30): Collection
    {
        return $this->expiringCertifications($tenantId, $days)
            ->merge($this->expiringDocuments($tenantId, $days))
            ->merge($this->expiringSiteDocuments($tenantId, $days))
            ->merge($this->expiringTraining($tenantId, $days));
    }

    public function slaCoverage(int $tenantId): array
    {
        $activeSites = Site::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->count();

        $sitesWithSla = SiteSlaRequirement::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->distinct('site_id')
            ->count('site_id');

        $requirementCount = SiteSlaRequirement::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();

        return [
            'active_sites' => $activeSites,
            'sites_with_sla' => $sitesWithSla,
            'requirement_count' => $requirementCount,
            'coverage_percent' => $activeSites > 0 ? round(($sitesWithSla / $activeSites) * 100, 1) : 0,
        ];
    }

    public function summary(int $tenantId, int $days = 30): array
    {
        return [
            'expiring_certs' => $this->expiringCertifications($tenantId, $days)->count(),
            'expiring_guard_docs' => $this->expiringDocuments($tenantId, $days)->count(),
            'expiring_site_docs' => $this->expiringSiteDocuments($tenantId, $days)->count(),
            'expiring_training' => $this->expiringTraining($tenantId, $days)->count(),
            'sla' => $this->slaCoverage($tenantId),
        ];
    }
}
