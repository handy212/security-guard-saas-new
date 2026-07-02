<?php

namespace App\Console\Commands;

use App\Models\AttendanceLog;
use App\Models\GuardIdleAlert;
use App\Models\GuardLocation;
use App\Models\Tenant;
use App\Services\NotificationDispatcher;
use Illuminate\Console\Command;

class DetectIdleGuards extends Command
{
    protected $signature = 'guardops:detect-idle-guards';

    protected $description = 'Alert supervisors when on-duty guards have not reported location recently';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        $threshold = config('notifications.idle_alert_minutes', 15);

        Tenant::where('status', 'active')->each(function (Tenant $tenant) use ($dispatcher, $threshold) {
            $onDuty = AttendanceLog::where('tenant_id', $tenant->id)
                ->whereNull('clock_out_at')
                ->with('assignedGuard')
                ->get();

            foreach ($onDuty as $log) {
                $lastLocation = GuardLocation::where('guard_id', $log->guard_id)
                    ->latest('recorded_at')
                    ->first();

                if (! $lastLocation) {
                    continue;
                }

                $idleMinutes = $lastLocation->recorded_at->diffInMinutes(now());
                if ($idleMinutes < $threshold) {
                    GuardIdleAlert::where('tenant_id', $tenant->id)
                        ->where('guard_id', $log->guard_id)
                        ->whereNull('resolved_at')
                        ->update(['resolved_at' => now()]);

                    continue;
                }

                $existing = GuardIdleAlert::where('tenant_id', $tenant->id)
                    ->where('guard_id', $log->guard_id)
                    ->whereNull('resolved_at')
                    ->where('alerted_at', '>=', now()->subHour())
                    ->exists();

                if ($existing) {
                    continue;
                }

                GuardIdleAlert::create([
                    'tenant_id' => $tenant->id,
                    'guard_id' => $log->guard_id,
                    'last_location_at' => $lastLocation->recorded_at,
                    'idle_minutes' => $idleMinutes,
                    'alerted_at' => now(),
                ]);

                $dispatcher->sendToTenantAdmins($tenant->id, 'guard.idle', [
                    'guard' => $log->assignedGuard?->full_name ?? 'Guard',
                    'minutes' => $idleMinutes,
                    'site' => $log->site?->name ?? 'Unknown',
                ], actionUrl: '/tracking');
            }
        });

        return self::SUCCESS;
    }
}
