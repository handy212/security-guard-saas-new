<?php

namespace App\Console\Commands;

use App\Services\ClientReportDeliveryService;
use Illuminate\Console\Command;

class DeliverScheduledClientReports extends Command
{
    protected $signature = 'guardops:deliver-scheduled-client-reports';

    protected $description = 'Send due client report notices by email or WhatsApp';

    public function handle(ClientReportDeliveryService $delivery): int
    {
        $count = $delivery->deliverDueSchedules();
        $this->info("Submitted {$count} scheduled client report notice(s).");

        return self::SUCCESS;
    }
}
