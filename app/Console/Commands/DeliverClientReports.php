<?php

namespace App\Console\Commands;

use App\Models\CustomReportSubmission;
use App\Models\Tenant;
use App\Services\CustomReportService;
use Illuminate\Console\Command;

class DeliverClientReports extends Command
{
    protected $signature = 'guardops:deliver-client-reports';

    protected $description = 'Deliver approved custom reports to client portal users';

    public function handle(CustomReportService $reports): int
    {
        Tenant::where('status', 'active')->each(function (Tenant $tenant) use ($reports) {
            CustomReportSubmission::where('tenant_id', $tenant->id)
                ->where('status', 'submitted')
                ->whereNull('delivered_at')
                ->each(fn ($submission) => $reports->deliverToClient($submission));
        });

        return self::SUCCESS;
    }
}
