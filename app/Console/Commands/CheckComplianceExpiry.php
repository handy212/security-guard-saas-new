<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ComplianceService;
use App\Services\NotificationDispatcher;
use Illuminate\Console\Command;

class CheckComplianceExpiry extends Command
{
    protected $signature = 'guardops:compliance-expiry';

    protected $description = 'Notify admins about expiring guard certifications and documents';

    public function handle(ComplianceService $compliance, NotificationDispatcher $dispatcher): int
    {
        Tenant::where('status', 'active')->each(function (Tenant $tenant) use ($compliance, $dispatcher) {
            $expiring = $compliance->expiringWithinDays($tenant->id, 30);

            if ($expiring->isEmpty()) {
                return;
            }

            $dispatcher->sendToTenantAdmins($tenant->id, 'compliance.expiring', [
                'count' => $expiring->count(),
                'tenant' => $tenant->name,
            ]);
        });

        return self::SUCCESS;
    }
}
