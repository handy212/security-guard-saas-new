<?php

namespace App\Console\Commands;

use App\Enums\DispatchStatus;
use App\Models\DispatchEvent;
use App\Models\Tenant;
use App\Services\PatriotAccountProvisioner;
use App\Services\PatriotDispatchClient;
use App\Services\PlanEntitlementService;
use Illuminate\Console\Command;
use RuntimeException;

class SendPatriotDispatchUpdates extends Command
{
    protected $signature = 'guardops:patriot-send-updates';

    protected $description = 'Send GuardCore dispatch decisions and field updates to Patriot';

    public function handle(PatriotDispatchClient $patriot, PlanEntitlementService $plans, PatriotAccountProvisioner $provisioner): int
    {
        if (! config('services.patriot.writeback_enabled')) {
            return self::SUCCESS;
        }

        $tenant = Tenant::where('slug', config('services.patriot.tenant'))->where('status', 'active')->first();
        if (! $tenant || ! $plans->tenantHasFeature($tenant->id, 'dispatch')) {
            $this->error('Configure an active tenant with Dispatch access.');

            return self::FAILURE;
        }

        $pending = DispatchEvent::forTenant($tenant->id)->whereNotNull('patriot_job_id')->whereNotNull('patriot_pending_action')
            ->where(fn ($query) => $query->whereNull('patriot_instance')->orWhere('patriot_instance', $provisioner->instance()));
        (clone $pending)->where('patriot_writeback_started_at', '<', now()->subMinutes(5))->whereNull('patriot_writeback_error')
            ->update(['patriot_writeback_error' => 'The previous attempt was interrupted. Verify the job in Patriot.']);

        $failed = false;
        foreach ($pending->whereNull('patriot_writeback_started_at')->whereNull('patriot_writeback_error')->oldest('updated_at')->limit(10)->get() as $event) {
            $queuedAction = $event->patriot_pending_action;
            $reconcile = str_starts_with($queuedAction, 'check:');
            $action = $reconcile ? substr($queuedAction, 6) : $queuedAction;
            $claim = DispatchEvent::forTenant($tenant->id)->whereKey($event->id)->where('patriot_pending_action', $queuedAction)
                ->whereNull('patriot_writeback_started_at')->whereNull('patriot_writeback_error');
            if (! $claim->update(['patriot_writeback_started_at' => now()])) {
                continue;
            }

            $event->refresh();
            try {
                $patriot->checkWriteback((int) $event->patriot_job_id, $action, ! $reconcile, $action === 'complete' ? $event->action_taken : null, $reconcile);
            } catch (RuntimeException $exception) {
                $event->update(['patriot_writeback_error' => $exception->getMessage()]);
                $failed = true;

                continue;
            }

            $event->update([
                'patriot_response_state' => match ($action) {
                    'accept' => 'accepted',
                    'decline' => 'declined',
                    'on-site' => 'on_site',
                    'complete' => 'completed',
                },
                'patriot_pending_action' => null,
                'patriot_writeback_started_at' => null,
                'patriot_writeback_error' => null,
            ] + ($action === 'decline' ? ['status' => DispatchStatus::CANCELLED, 'closed_at' => now(), 'returned_at' => $event->dispatch_team_id ? now() : null] : []));
            $event->activityLogs()->create([
                'tenant_id' => $tenant->id,
                'action' => 'patriot_synced',
                'message' => 'Patriot confirmed '.$action,
            ]);
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
