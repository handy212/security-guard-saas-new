<?php

namespace App\Services;

use App\Enums\DispatchStatus;
use App\Models\ClientAccount;
use App\Models\DispatchEvent;
use App\Models\PatriotAccountMapping;
use App\Models\Site;
use Illuminate\Support\Collection;

class PatriotAccountProvisioner
{
    public function instance(): string
    {
        return hash('sha256', (string) (config('services.patriot.instance') ?: strtolower(rtrim((string) config('services.patriot.url'), '/'))));
    }

    /**
     * The caller holds the tenant row lock for intake, enrichment and reconciliation.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>|null  $client
     */
    public function link(DispatchEvent $event, array $payload, ?array $client = null, bool $allowProvisional = true): void
    {
        $reference = $event->patriot_client_ref;
        if ($reference === null) {
            $event->update(['patriot_sync_status' => 'review']);

            return;
        }

        $mapping = $this->mapping($event);
        $site = $this->site($event->tenant_id, $mapping->site_id);
        $canRelink = $event->status === DispatchStatus::OPEN && ! $event->incident_id && ! $event->guard_id;
        $legacy = Site::forTenant($event->tenant_id)->where('patriot_client_ref', $reference)->first();
        $site ??= $legacy ? $this->site($event->tenant_id, $legacy->id) : null;
        if ($legacy && $site?->id === $legacy->id) {
            $mapping->verification_status = 'verified';
        }

        $billing = $client['billing_reference'] ?? $mapping->billing_reference;
        $siteReference = $client['site_reference'] ?? $mapping->site_reference;
        $name = $payload['customer_name'] ?? null;
        $location = $payload['location'];

        if ($mapping->verification_status === 'verified' && ! $site) {
            $mapping->verification_status = 'pending';
        }

        $ambiguousBilling = false;
        if ($mapping->verification_status !== 'verified' && $client !== null) {
            $matchingSite = $this->matchingSite($event, $siteReference, $location);
            $billingCustomers = $this->billingCustomers($event, $billing);
            $ambiguousBilling = $billingCustomers->count() > 1;
            if ($matchingSite && $billingCustomers->isNotEmpty() && ! $billingCustomers->contains('id', $matchingSite->client_account_id)) {
                $matchingSite = null;
                $ambiguousBilling = true;
            }
            $customer = $matchingSite?->clientAccount ?? (! $ambiguousBilling ? $billingCustomers->first() : null);
            if ($matchingSite) {
                $site = $matchingSite;
            } elseif ($customer && $site?->client_account_id !== $customer->id) {
                $site = $this->createSite($customer, $name ?? $customer->name, $location) ?? $site;
            }

            $mapping->verification_status = $site && ($matchingSite || $customer) ? 'synchronized' : 'review';
        }

        if ($allowProvisional && ! $site && $name && ($location['address'] || $location['latitude'] !== null)) {
            $customer = new ClientAccount(['tenant_id' => $event->tenant_id, 'name' => $name]);
            $customer->forceFill(['imported_from_patriot' => true])->save();
            $site = $this->createSite($customer, $name, $location);
        }

        $mapping->fill([
            'account_number' => $payload['account_number'] ?? $mapping->account_number,
            'billing_reference' => $billing,
            'site_reference' => $siteReference,
            'site_id' => $site?->id,
        ]);
        if ($client !== null) {
            $mapping->synchronized_at = now();
            $details = $client['response_details'];
            if (in_array($details['contact_sync_status'] ?? null, ['unavailable', 'access_denied'], true)) {
                unset($details['contacts']);
            }
            $mapping->response_details = $details + ($mapping->response_details ?? []);
            if ($mapping->verification_status !== 'verified' && $site && $billing && ! $ambiguousBilling && config('services.patriot.group_by_billing_account')) {
                $mapping->verification_status = 'synchronized';
            }
        }
        $mapping->save();

        $event->update([
            'patriot_sync_status' => $site ? $mapping->verification_status : ($client === null ? 'pending' : 'review'),
        ] + ($canRelink || (! $event->site_id && ! $event->incident_id) ? [
            'site_id' => $site?->id,
            'client_account_id' => $site?->client_account_id,
        ] : []));
    }

    public function reconcile(DispatchEvent $event, Site $site): void
    {
        abort_unless($site->tenant_id === $event->tenant_id && $this->site($event->tenant_id, $site->id), 422);

        if ($event->patriot_client_ref !== null) {
            $this->mapping($event)->fill(['site_id' => $site->id, 'verification_status' => 'verified'])->save();
        }

        $pending = DispatchEvent::forTenant($event->tenant_id)->whereNotNull('patriot_job_id')
            ->where('status', DispatchStatus::OPEN)->whereNull('guard_id')->whereNull('incident_id');
        if ($event->patriot_client_ref === null) {
            $pending->whereKey($event->id);
        } else {
            $pending->where('patriot_client_ref', $event->patriot_client_ref)
                ->where(fn ($query) => $query->whereNull('patriot_instance')->orWhere('patriot_instance', $event->patriot_instance));
        }
        $pending->update(['site_id' => $site->id, 'client_account_id' => $site->client_account_id, 'patriot_sync_status' => 'verified']);
    }

    /** @return array<string, mixed> */
    public function diagnostics(DispatchEvent $event): array
    {
        if ($event->patriot_client_ref === null) {
            return [];
        }

        $mapping = $this->mapping($event);

        return [
            'verification_status' => $mapping->verification_status,
            'synchronized_at' => $mapping->synchronized_at?->toIso8601String(),
            'response_details' => $mapping->response_details,
        ];
    }

    private function mapping(DispatchEvent $event): PatriotAccountMapping
    {
        return PatriotAccountMapping::forTenant($event->tenant_id)->firstOrNew([
            'tenant_id' => $event->tenant_id,
            'instance' => $event->patriot_instance ?? $this->instance(),
            'account_reference' => $event->patriot_client_ref,
        ], ['verification_status' => 'pending']);
    }

    private function site(int $tenantId, ?int $siteId): ?Site
    {
        return $siteId === null ? null : Site::forTenant($tenantId)
            ->whereIn('client_account_id', ClientAccount::forTenant($tenantId)->select('id'))
            ->find($siteId);
    }

    /** @param array<string, mixed> $location */
    private function matchingSite(DispatchEvent $event, ?string $reference, array $location): ?Site
    {
        if ($reference === null) {
            return null;
        }

        $siteIds = PatriotAccountMapping::forTenant($event->tenant_id)->where('instance', $event->patriot_instance)
            ->where('site_reference', $reference)->where('account_reference', '!=', $event->patriot_client_ref)
            ->whereNotNull('site_id')->distinct()->pluck('site_id');
        if ($siteIds->count() !== 1) {
            return null;
        }

        $site = $this->site($event->tenant_id, $siteIds->first());
        if (! $site) {
            return null;
        }

        $compareCoordinates = $location['latitude'] !== null && $site->latitude !== null && $site->longitude !== null;
        $sameCoordinates = $compareCoordinates && abs($location['latitude'] - $site->latitude) < 0.000001
            && abs($location['longitude'] - $site->longitude) < 0.000001;
        $compareAddress = $location['address'] && $site->address;
        $sameAddress = $compareAddress && strcasecmp($location['address'], $site->address) === 0;

        return ($sameCoordinates || $sameAddress) && (! $compareAddress || $sameAddress) && (! $compareCoordinates || $sameCoordinates)
            ? $site : null;
    }

    /** @return Collection<int, ClientAccount> */
    private function billingCustomers(DispatchEvent $event, ?string $billing): Collection
    {
        if ($billing === null || ! config('services.patriot.group_by_billing_account')) {
            return collect();
        }

        $siteIds = PatriotAccountMapping::forTenant($event->tenant_id)->where('instance', $event->patriot_instance)
            ->where('billing_reference', $billing)->where('account_reference', '!=', $event->patriot_client_ref)
            ->whereNotNull('site_id')->select('site_id');
        $customerIds = Site::forTenant($event->tenant_id)->whereIn('id', $siteIds)->select('client_account_id');

        return ClientAccount::forTenant($event->tenant_id)->whereIn('id', $customerIds)->limit(2)->get();
    }

    /** @param array<string, mixed> $location */
    private function createSite(ClientAccount $customer, ?string $name, array $location): ?Site
    {
        if (! $name || (! $location['address'] && $location['latitude'] === null)) {
            return null;
        }

        $site = new Site(['tenant_id' => $customer->tenant_id, 'name' => $name, 'client_account_id' => $customer->id] + $location);
        $site->forceFill(['imported_from_patriot' => true])->save();

        return $site;
    }
}
