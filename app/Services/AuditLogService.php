<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    public function record(string $action, ?Model $auditable = null, array $metadata = []): AuditLog
    {
        $tenantId = null;

        try {
            $tenantId = TenantContext::id();
        } catch (\Throwable) {
            // Platform console actions may run without tenant context.
        }

        return AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'metadata' => array_merge($metadata, [
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]),
        ]);
    }

    public function recordPlatform(string $action, ?Model $auditable = null, array $metadata = [], ?int $subjectTenantId = null): AuditLog
    {
        return AuditLog::create([
            'tenant_id' => $subjectTenantId,
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'metadata' => array_merge($metadata, [
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'platform' => true,
            ]),
        ]);
    }
}
