<?php

namespace App\Services;

use App\Models\DataRetentionPolicy;
use App\Models\IncidentEscalationRule;
use App\Models\Site;
use App\Models\SiteSlaRequirement;

class CompliancePolicyService
{
    public function escalationRules(int $tenantId)
    {
        return IncidentEscalationRule::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
    }

    public function retentionPolicies(int $tenantId)
    {
        return DataRetentionPolicy::query()
            ->where('tenant_id', $tenantId)
            ->get();
    }

    public function tenantSlaCoverage(int $tenantId): float
    {
        return app(ComplianceService::class)->slaCoverage($tenantId)['coverage_percent'];
    }

    public function siteSlaSummary(int $siteId): array
    {
        $requirements = SiteSlaRequirement::query()
            ->where('site_id', $siteId)
            ->where('is_active', true)
            ->get();

        return [
            'requirements' => $requirements->count(),
            'score' => $requirements->count() > 0 ? 100 : 0,
        ];
    }
}
