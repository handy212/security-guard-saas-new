<?php

namespace App\Services;

use App\Models\GuardCertification;
use App\Models\GuardDocument;

class ComplianceService
{
    public function expiringCertifications(int $tenantId, int $days = 30)
    {
        return GuardCertification::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('expires_at', '<=', now()->addDays($days))
            ->get();
    }

    public function expiringDocuments(int $tenantId, int $days = 30)
    {
        return GuardDocument::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('expires_at', '<=', now()->addDays($days))
            ->get();
    }
}
