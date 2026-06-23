<?php

namespace App\Console\Commands;

use App\Models\PatrolSession;
use App\Models\Tenant;
use App\Services\NotificationDispatcher;
use Illuminate\Console\Command;

class DetectMissedPatrols extends Command
{
    protected $signature = 'guardops:missed-patrols';

    protected $description = 'Flag overdue patrol sessions and notify supervisors';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        Tenant::where('status', 'active')->each(function (Tenant $tenant) use ($dispatcher) {
            $missed = PatrolSession::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'in_progress')
                ->where('started_at', '<', now()->subHours(2))
                ->get();

            foreach ($missed as $session) {
                $session->update(['status' => 'missed']);
            }

            if ($missed->isNotEmpty()) {
                $dispatcher->sendToTenantAdmins($tenant->id, 'patrol.missed', [
                    'count' => $missed->count(),
                    'tenant' => $tenant->name,
                ]);
            }
        });

        return self::SUCCESS;
    }
}
