<?php

namespace App\Services;

use App\Models\{DataRetentionPolicy, IncidentEscalationRule, SiteSlaRequirement};

class CompliancePolicyService
{
    public function escalationRules(int $tenantId)
    {
        return IncidentEscalationRule::where('tenant_id', $tenantId)->where('is_active', true)->get();
    }

    public function slaScore(int $siteId): array
    {
        $requirements = SiteSlaRequirement::where('site_id', $siteId)->where('is_active', true)->get();
        return ['requirements' => $requirements->count(), 'score' => $requirements->count() ? 100 : 0];
    }

    public function retentionPolicies(int $tenantId)
    {
        return DataRetentionPolicy::where('tenant_id', $tenantId)->get();
    }
}
