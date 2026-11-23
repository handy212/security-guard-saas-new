<?php

namespace App\Console\Commands;

use App\Enums\DispatchStatus;
use App\Models\DispatchEvent;
use App\Models\Site;
use App\Models\Tenant;
use App\Services\PatriotDispatchClient;
use App\Services\PatriotDispatchPayload;
use App\Services\PlanEntitlementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ImportPatriotDispatches extends Command
{
    protected $signature = 'guardops:patriot-import {--job-id= : Import only this Patriot job} {--scheduled : Run only when automatic intake is enabled}';

    protected $description = 'Import pending Patriot requests into the configured tenant dispatch queue';

    public function handle(PatriotDispatchClient $patriot, PlanEntitlementService $plans, PatriotDispatchPayload $payloads): int
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
        [$created, $removed] = DB::transaction(function () use ($tenant, $jobs, $snapshot, $reconcile, $payloads): array {
            Tenant::query()->lockForUpdate()->findOrFail($tenant->id);

            $created = 0;
            foreach ($jobs as $job) {
                $payload = $payloads->fromJob($job);
                $existing = DispatchEvent::forTenant($tenant->id)->where('patriot_job_id', $job->jobID)->first();
                if ($existing) {
                    DispatchEvent::withoutTimestamps(fn () => $existing->update(['patriot_payload' => $payload]));

                    continue;
                }
                if (($job->status ?? null) !== 'DispatchRequest') {
                    continue;
                }

                $reference = is_int($job->clientRef ?? null) || is_string($job->clientRef ?? null) ? trim((string) $job->clientRef) : '';
                $reference = $reference !== '' && mb_strlen($reference) <= 160 ? $reference : null;
                $site = $reference === null ? null : Site::forTenant($tenant->id)->where('patriot_client_ref', $reference)->first();
                $dispatch = DispatchEvent::forTenant($tenant->id)->firstOrCreate([
                    'tenant_id' => $tenant->id,
                    'patriot_job_id' => $job->jobID,
                ], [
                    'dispatch_number' => 'PAT-'.$job->jobID,
                    'patriot_client_ref' => $reference,
                    'site_id' => $site?->id,
                    'client_account_id' => $site?->client_account_id,
                    'event_type' => 'alarm',
                    'status' => DispatchStatus::OPEN,
                    'opened_at' => now(),
                    'patriot_payload' => $payload,
                    'description' => $payload['alarm_description'],
                    'incident_location' => $payload['location']['address'] ? Str::limit($payload['location']['address'], 240) : null,
                ]);

                $created += (int) $dispatch->wasRecentlyCreated;
            }

            $removed = $reconcile ? $this->removeMissingRequests($tenant->id, $snapshot->pluck('jobID')->all()) : 0;

            return [$created, $removed];
        }, attempts: 3);

        if ($this->option('scheduled') && $created > 0) {
            logger()->info('Patriot dispatches imported.', ['tenant_id' => $tenant->id, 'created' => $created]);
        }

        if ($removed > 0) {
            logger()->info('Withdrawn Patriot requests removed from the dispatch queue.', ['tenant_id' => $tenant->id, 'removed' => $removed]);
        }

        $this->info("Imported {$created} new Patriot dispatches. Removed {$removed} withdrawn requests.");

        return self::SUCCESS;
    }

    /** @param list<int> $visibleIds */
    private function removeMissingRequests(int $tenantId, array $visibleIds): int
    {
        $unassigned = DispatchEvent::forTenant($tenantId)
            ->whereNotNull('patriot_job_id')
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
        $removed = (clone $missing)->where('patriot_missing_since', '<=', $observedAt->copy()->subMinute())->delete();
        $missing->whereNull('patriot_missing_since')->update(['patriot_missing_since' => $observedAt]);

        return $removed;
    }
}
