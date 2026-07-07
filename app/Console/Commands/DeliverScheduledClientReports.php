<?php

namespace App\Console\Commands;

use App\Services\ClientReportDeliveryService;
use Illuminate\Console\Command;

class DeliverScheduledClientReports extends Command
{
    protected $signature = 'guardops:deliver-scheduled-client-reports';

    protected $description = 'Send due client email report schedules';

    public function handle(ClientReportDeliveryService $delivery): int
    {
        $count = $delivery->deliverDueSchedules();
        $this->info("Delivered {$count} scheduled client report(s).");

        return self::SUCCESS;
    }
}
