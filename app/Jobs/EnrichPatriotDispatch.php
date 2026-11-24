<?php

namespace App\Jobs;

use App\Enums\DispatchStatus;
use App\Models\DispatchEvent;
use App\Models\Tenant;
use App\Services\PatriotAccountProvisioner;
use App\Services\PatriotDispatchClient;
use App\Services\PatriotDispatchPayload;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class EnrichPatriotDispatch implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 50;

    public int $uniqueFor = 3600;

    /** @var list<int> */
    public array $backoff = [30, 120, 600];

    public function __construct(public int $tenantId, public int $dispatchId, public bool $refresh = false) {}

    public function uniqueId(): string
    {
        return "{$this->tenantId}:{$this->dispatchId}";
    }

    public function handle(PatriotDispatchClient $patriot, PatriotDispatchPayload $payloads, PatriotAccountProvisioner $provisioner): void
    {
        $event = $this->eligible()->find($this->dispatchId);
        if (! $event || $event->patriot_instance !== $provisioner->instance()
            || ! preg_match('/\A[1-9][0-9]{0,9}\z/', (string) $event->patriot_client_ref)
            || (int) $event->patriot_client_ref > 2147483647) {
            return;
        }

        try {
            $client = $payloads->fromClient($patriot->client($event->patriot_client_ref));
        } catch (RuntimeException $exception) {
            DB::transaction(function () use ($provisioner): void {
                Tenant::query()->lockForUpdate()->findOrFail($this->tenantId);
                $event = $this->eligible()->lockForUpdate()->find($this->dispatchId);
                if ($event && ! $event->site_id) {
                    $provisioner->link($event, $event->patriot_payload);
                }
            }, attempts: 3);

            throw $exception;
        }
        DB::transaction(function () use ($client, $provisioner): void {
            Tenant::query()->lockForUpdate()->findOrFail($this->tenantId);
            $event = $this->eligible()->lockForUpdate()->find($this->dispatchId);
            if (! $event) {
                return;
            }

            $payload = $event->patriot_payload;
            foreach (['customer_name', 'account_number', 'instructions'] as $field) {
                $payload[$field] ??= $client[$field];
            }
            $payload['location']['address'] ??= $client['location']['address'];
            if (($payload['location']['latitude'] ?? null) === null) {
                $payload['location']['latitude'] = $client['location']['latitude'];
                $payload['location']['longitude'] = $client['location']['longitude'];
            }

            $event->update([
                'patriot_payload' => $payload,
                'incident_location' => $event->incident_location ?? ($payload['location']['address'] ? Str::limit($payload['location']['address'], 240) : null),
                'latitude' => $event->latitude ?? $payload['location']['latitude'],
                'longitude' => $event->longitude ?? $payload['location']['longitude'],
            ]);
            $provisioner->link($event, $payload, $client);
        }, attempts: 3);
    }

    public function failed(?Throwable $exception): void
    {
        $this->eligible()->whereKey($this->dispatchId)->where('patriot_sync_status', '!=', 'verified')->update(['patriot_sync_status' => 'failed']);
        logger()->warning('Patriot client enrichment exhausted its retries.', [
            'tenant_id' => $this->tenantId, 'dispatch_id' => $this->dispatchId,
            'exception' => $exception ? $exception::class : null,
        ]);
    }

    private function eligible(): Builder
    {
        return DispatchEvent::forTenant($this->tenantId)->whereNotNull('patriot_job_id')
            ->whereIn('patriot_sync_status', ($this->refresh ?? false)
                ? ['pending', 'verified', 'failed', 'review', 'synchronized']
                : ['pending', 'verified', 'failed'])
            ->where('status', '!=', DispatchStatus::CANCELLED);
    }
}
