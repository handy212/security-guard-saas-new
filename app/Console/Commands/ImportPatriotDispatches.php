<?php

namespace App\Console\Commands;

use App\Enums\DispatchPriority;
use App\Enums\DispatchStatus;
use App\Jobs\EnrichPatriotDispatch;
use App\Models\DispatchEvent;
use App\Models\Tenant;
use App\Services\DispatchService;
use App\Services\PatriotAccountProvisioner;
use App\Services\PatriotDispatchClient;
use App\Services\PatriotDispatchPayload;
use App\Services\PlanEntitlementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ImportPatriotDispatches extends Command
{
    protected $signature = 'guardops:patriot-import {--job-id= : Import only this Patriot job} {--scheduled : Run only when automatic intake is enabled}';

    protected $description = 'Import pending Patriot requests into the configured tenant dispatch queue';

    public function handle(PatriotDispatchClient $patriot, PlanEntitlementService $plans, PatriotDispatchPayload $payloads, PatriotAccountProvisioner $provisioner, DispatchService $dispatches): int
    {
        if ($this->option('scheduled') && ! config('services.patriot.polling_enabled')) {
            return self::SUCCESS;
        }

        $tenantSlug = config('services.patriot.tenant');
        $tenant = $tenantSlug ? Tenant::where('slug', $tenantSlug)->where('status', 'active')->first() : null;

        if (! $tenant || ! $plans->tenantHasFeature($tenant->id, 'dispatch')) {
            $this->error('Set PATRIOT_TENANT to an active tenant slug with Dispatch access.');

            return self::FAILURE;
        }

        $instance = $provisioner->instance();
        if (DispatchEvent::forTenant($tenant->id)->where('patriot_instance', '!=', $instance)->exists()) {
            $this->error('The tenant already has dispatches for a different Patriot source. Check PATRIOT_INSTANCE before importing.');

            return self::FAILURE;
        }

        $jobId = $this->option('job-id');
        if ($jobId !== null && ! preg_match('/\A[1-9][0-9]*\z/', $jobId)) {
            $this->error('The job ID must be a positive integer.');

            return self::FAILURE;
        }

        try {
            $snapshot = collect($patriot->pendingJobs());
            $jobs = $snapshot->filter(fn (object $job): bool => $job->jobID > 0
                && ($jobId === null || (string) $job->jobID === $jobId));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($jobId !== null && $jobs->isEmpty()) {
            $this->error('The requested job is not visible to the configured Patriot account.');

            return self::FAILURE;
        }

        $reconcile = $jobId === null && $this->option('scheduled');
        [$created, $removed, $enrich, $newDispatches] = DB::transaction(function () use ($tenant, $jobs, $snapshot, $reconcile, $payloads, $provisioner, $instance): array {
            Tenant::query()->lockForUpdate()->findOrFail($tenant->id);

            $newDispatches = [];
            $enrich = [];
            foreach ($jobs as $job) {
                $payload = $payloads->fromJob($job);
                $existing = DispatchEvent::forTenant($tenant->id)->where('patriot_job_id', $job->jobID)->lockForUpdate()->first();
                if ($existing) {
                    if (($existing->patriot_instance !== null && $existing->patriot_instance !== $instance)
                        || ! in_array($existing->status, [DispatchStatus::OPEN, DispatchStatus::ASSIGNED], true)) {
                        continue;
                    }

                    $payload = $this->fillMissing($existing->patriot_payload ?? [], $payload);
                    $reference = $existing->patriot_client_ref ?? $payloads->identifier($job->clientRef ?? null);
                    DispatchEvent::withoutTimestamps(fn () => $existing->update([
                        'patriot_payload' => $payload, 'patriot_instance' => $instance, 'patriot_client_ref' => $reference,
                        'priority' => $existing->priority === DispatchPriority::NORMAL
                            ? $payloads->dispatchPriority($payload['priority']) : $existing->priority,
                        'incident_location' => $existing->incident_location ?? ($payload['location']['address'] ? Str::limit($payload['location']['address'], 240) : null),
                        'latitude' => $existing->latitude ?? $payload['location']['latitude'],
                        'longitude' => $existing->longitude ?? $payload['location']['longitude'],
                    ]));
                    if ($existing->patriot_sync_status === null && $existing->site_id) {
                        $existing->update(['patriot_sync_status' => 'verified']);
                    } elseif ($existing->patriot_sync_status === null || (in_array($existing->patriot_sync_status, ['pending', 'review'], true) && ! $existing->site_id)) {
                        $provisioner->link($existing, $payload, allowProvisional: ! $this->canEnrich($reference));
                    }
                    if ($existing->patriot_sync_status === 'pending' && $this->canEnrich($reference)) {
                        $enrich[] = $existing->id;
                    }

                    continue;
                }
                if (($job->status ?? null) !== 'DispatchRequest') {
                    continue;
                }

                $reference = $payloads->identifier($job->clientRef ?? null);
                $dispatch = DispatchEvent::forTenant($tenant->id)->firstOrCreate([
                    'tenant_id' => $tenant->id,
                    'patriot_job_id' => $job->jobID,
                ], [
                    'dispatch_number' => 'PAT-'.$job->jobID,
                    'patriot_client_ref' => $reference,
                    'patriot_instance' => $instance,
                    'event_type' => 'alarm',
                    'priority' => $payloads->dispatchPriority($payload['priority']),
                    'status' => DispatchStatus::OPEN,
                    'opened_at' => now(),
                    'patriot_payload' => $payload,
                    'patriot_intake_snapshot' => $payload,
                    'description' => $payload['alarm_description'],
                    'incident_location' => $payload['location']['address'] ? Str::limit($payload['location']['address'], 240) : null,
                    'latitude' => $payload['location']['latitude'],
                    'longitude' => $payload['location']['longitude'],
                ]);

                $provisioner->link($dispatch, $payload, allowProvisional: ! $this->canEnrich($reference));
                if ($this->canEnrich($reference)) {
                    $enrich[] = $dispatch->id;
                } elseif ($dispatch->patriot_sync_status === 'pending') {
                    $dispatch->update(['patriot_sync_status' => 'review']);
                }

                $newDispatches[] = $dispatch;
            }

            $removed = $reconcile ? $this->removeMissingRequests($tenant->id, $instance, $snapshot->pluck('jobID')->all()) : 0;

            return [count($newDispatches), $removed, $enrich, $newDispatches];
        }, attempts: 3);

        foreach ($enrich as $dispatchId) {
            try {
                EnrichPatriotDispatch::dispatch($tenant->id, $dispatchId);
            } catch (Throwable $exception) {
                logger()->warning('Could not run or queue Patriot client enrichment.', [
                    'tenant_id' => $tenant->id, 'dispatch_id' => $dispatchId, 'exception' => $exception::class,
                ]);
            }
        }

        foreach ($newDispatches as $dispatch) {
            $dispatches->notifyPatriotIntake($dispatch);
        }

        if ($this->option('scheduled') && $created > 0) {
            logger()->info('Patriot dispatches imported.', ['tenant_id' => $tenant->id, 'created' => $created]);
        }

        if ($removed > 0) {
            logger()->info('Withdrawn Patriot requests removed from the dispatch queue.', ['tenant_id' => $tenant->id, 'removed' => $removed]);
        }

        $this->info("Imported {$created} new Patriot dispatches. Removed {$removed} withdrawn requests.");

        return self::SUCCESS;
    }

    private function canEnrich(?string $reference): bool
    {
        return config('services.patriot.enrichment_enabled')
            && preg_match('/\A[1-9][0-9]{0,9}\z/', $reference ?? '') === 1
            && (int) $reference <= 2147483647;
    }

    /**
     * @param  array<string, mixed>  $stored
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function fillMissing(array $stored, array $incoming): array
    {
        foreach ($incoming as $key => $value) {
            $stored[$key] ??= $value;
        }

        $stored['location']['address'] ??= $incoming['location']['address'];
        if (($stored['location']['latitude'] ?? null) === null) {
            $stored['location']['latitude'] = $incoming['location']['latitude'];
            $stored['location']['longitude'] = $incoming['location']['longitude'];
        }

        return $stored;
    }

    /** @param list<int> $visibleIds */
    private function removeMissingRequests(int $tenantId, string $instance, array $visibleIds): int
    {
        $unassigned = DispatchEvent::forTenant($tenantId)
            ->whereNotNull('patriot_job_id')
            ->where(fn ($query) => $query->whereNull('patriot_instance')->orWhere('patriot_instance', $instance))
            ->where('status', DispatchStatus::OPEN)
            ->whereNull('assigned_at')
            ->whereNull('guard_id')
            ->whereNull('dispatch_team_id')
            ->whereNull('incident_id')
            ->whereNull('patriot_response_state')
            ->whereNull('patriot_pending_action')
            ->whereNull('patriot_writeback_error')
            ->whereDoesntHave('activityLogs');

        (clone $unassigned)->whereNotNull('patriot_missing_since')
            ->whereIntegerInRaw('patriot_job_id', $visibleIds)->update(['patriot_missing_since' => null]);

        $missing = $unassigned->whereIntegerNotInRaw('patriot_job_id', $visibleIds);
        $observedAt = now();
        $withdrawn = (clone $missing)->where('patriot_missing_since', '<=', $observedAt->copy()->subMinute());
        $removed = $withdrawn->update(['status' => DispatchStatus::CANCELLED, 'closed_at' => $observedAt]);
        $missing->whereNull('patriot_missing_since')->update(['patriot_missing_since' => $observedAt]);

        return $removed;
    }
}
