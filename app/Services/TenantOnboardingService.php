<?php

namespace App\Services;

use App\Models\ClientAccount;
use App\Models\Guard;
use App\Models\Shift;
use App\Models\Site;
use App\Models\TenantSubscription;

class TenantOnboardingService
{
    public function steps(int $tenantId): array
    {
        $hasClient = ClientAccount::where('tenant_id', $tenantId)->exists();
        $hasSite = Site::where('tenant_id', $tenantId)->exists();
        $hasGuard = Guard::where('tenant_id', $tenantId)->exists();
        $hasShift = Shift::where('tenant_id', $tenantId)->exists();
        $hasSubscription = TenantSubscription::where('tenant_id', $tenantId)->whereIn('status', ['active', 'trial'])->exists();

        return [
            [
                'key' => 'client',
                'label' => 'Add your first client',
                'done' => $hasClient,
                'href' => '/clients',
            ],
            [
                'key' => 'site',
                'label' => 'Create a site / location',
                'done' => $hasSite,
                'href' => '/sites',
            ],
            [
                'key' => 'guard',
                'label' => 'Register a guard',
                'done' => $hasGuard,
                'href' => '/guards',
            ],
            [
                'key' => 'shift',
                'label' => 'Schedule your first shift',
                'done' => $hasShift,
                'href' => '/schedules',
            ],
            [
                'key' => 'subscription',
                'label' => 'Choose a subscription plan',
                'done' => $hasSubscription,
                'href' => '/billing/subscription',
            ],
        ];
    }

    public function progress(int $tenantId): int
    {
        $steps = $this->steps($tenantId);
        $done = collect($steps)->where('done', true)->count();

        return (int) round($done / max(count($steps), 1) * 100);
    }

    public function isComplete(int $tenantId): bool
    {
        return $this->progress($tenantId) === 100;
    }
}
