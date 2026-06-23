<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\AnalyticsService;
use App\Services\ComplianceService;
use App\Services\NotificationDispatcher;
use Illuminate\Console\Command;

class RunAnalyticsSnapshots extends Command
{
    protected $signature = 'guardops:analytics-snapshot {--date=}';

    protected $description = 'Generate daily analytics snapshots for all active tenants';

    public function handle(AnalyticsService $analytics): int
    {
        $date = $this->option('date');

        Tenant::where('status', 'active')->each(function (Tenant $tenant) use ($analytics, $date) {
            $analytics->snapshot($tenant->id, $date);
            $this->line("Snapshot created for tenant {$tenant->id}");
        });

        return self::SUCCESS;
    }
}
