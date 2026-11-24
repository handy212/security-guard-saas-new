<?php

namespace App\Services;

use App\Enums\DispatchPriority;
use App\Models\DispatchEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PatriotDispatchPayload
{
    /** @return array<string, mixed> */
    public function fromJob(object $job): array
    {
        $address = collect([$job->location ?? null, $job->location2 ?? null, $job->location3 ?? null,
            $job->city ?? null, $job->state ?? null, $job->postcode ?? null, $job->country ?? null])
            ->map(fn (mixed $line) => $this->cleanText($line, 255))->filter()->unique()->implode(', ');
        $position = $this->position($job->latitude ?? null, $job->longitude ?? null);

        return [
            'activation_id' => is_int($job->ticketNo ?? null) && $job->ticketNo > 0 ? $job->ticketNo : null,
            'account_number' => $this->cleanText($job->clientNo ?? null, 160),
            'customer_name' => $this->cleanText($job->clientName ?? null, 255),
            'dispatch_category' => $this->cleanText($job->category ?? null, 160),
            'alarm_description' => $this->cleanText($job->alarmDescription ?? null, 1000),
            'priority' => is_int($job->alarmPriority ?? null) ? $job->alarmPriority : null,
            'dispatch_time' => $this->dispatchTime($job->dispatchTime ?? null),
            'location' => ['address' => $this->cleanText($job->location ?? null, 255) === null ? null : $address] + $position,
            'instructions' => $this->cleanText($job->dispatchInstructions ?? null, 4000),
            'job_notes' => $this->cleanText($job->jobNotes ?? null, 4000),
        ];
    }

    public function dispatchPriority(?int $priority): DispatchPriority
    {
        return match (true) {
            $priority === 1 => DispatchPriority::CRITICAL,
            $priority === 2 => DispatchPriority::HIGH,
            $priority !== null && $priority >= 4 => DispatchPriority::LOW,
            default => DispatchPriority::NORMAL,
        };
    }

    /** @return array<string, mixed> */
    public function fromClient(object $client): array
    {
        $address = $client->location ?? null;

        return $this->fromJob((object) [
            'clientNo' => $client->clientNo ?? null,
            'clientName' => $client->name ?? null,
            'location' => $address->lineOne ?? null,
            'location2' => $address->lineTwo ?? null,
            'location3' => $address->lineThree ?? null,
            'city' => $address->cityName ?? null,
            'state' => $address->stateName ?? null,
            'postcode' => $address->zipPostCode ?? null,
            'country' => $address->countryName ?? null,
            'latitude' => $client->latitude ?? null,
            'longitude' => $client->longitude ?? null,
            'dispatchInstructions' => $client->alarmInstructions ?? null,
        ]) + [
            'billing_reference' => $this->identifier($client->accountNo ?? null),
            'response_details' => [
                'account_type' => $this->cleanText($client->accountType ?? null, 60),
                'site_phone' => $this->cleanText($client->sitePhone ?? null, 80),
            ] + $this->responseContacts($client->guardcoreCallList ?? null),
            'site_reference' => $this->identifier($client->baseAcct ?? null) ?? $this->identifier($client->clientNo ?? null),
        ];
    }

    /** @return array<string, mixed> */
    private function responseContacts(?array $callList): array
    {
        if ($callList === null) {
            return [];
        }

        $contacts = [];
        foreach ($callList['results'] as $contact) {
            $details = $contact->globalDetails ?? null;
            $record = array_filter([
                'name' => $this->cleanText($details->userName ?? null, 255),
                'telephone' => $this->cleanText($details->phoneNo ?? null, 80),
                'mobile' => $this->cleanText($details->mobileNo ?? null, 80),
                'after_hours' => $this->cleanText($details->afterHoursNo ?? null, 80),
                'call_order' => is_int($contact->siteDetails->callOrder ?? null) ? $contact->siteDetails->callOrder : null,
            ], fn (mixed $value): bool => $value !== null);
            if ($record !== []) {
                $contacts[] = $record;
            }
        }

        return ['contact_sync_status' => $callList['status'], 'contacts' => $contacts];
    }

    public function identifier(mixed $value): ?string
    {
        if (! is_string($value) && ! (is_int($value) && $value > 0)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' && mb_strlen($value) <= 160 && ! preg_match('/[\x00-\x1F\x7F]/', $value) ? $value : null;
    }

    /** @return array<string, mixed> */
    public function forDispatch(DispatchEvent $dispatch): array
    {
        $dispatch->loadMissing(['site', 'clientAccount']);
        $payload = $dispatch->patriot_payload ?? [];
        $site = $dispatch->site;
        $position = $this->position($payload['location']['latitude'] ?? null, $payload['location']['longitude'] ?? null);
        if ($position['latitude'] === null) {
            $position = $this->position($site?->latitude, $site?->longitude);
        }

        return [
            'source' => 'patriot',
            'dispatch_job_id' => (int) $dispatch->patriot_job_id,
            'event_id' => null,
            'activation_id' => $payload['activation_id'] ?? null,
            'client_reference' => $dispatch->patriot_client_ref,
            'account_number' => $payload['account_number'] ?? null,
            'customer_name' => $dispatch->clientAccount?->name ?? $payload['customer_name'] ?? null,
            'site_name' => $site?->name,
            'event_type' => null,
            'dispatch_category' => $payload['dispatch_category'] ?? null,
            'alarm_description' => $payload['alarm_description'] ?? $dispatch->description,
            'priority' => $payload['priority'] ?? null,
            'alarm_time' => null,
            'dispatch_time' => $payload['dispatch_time'] ?? null,
            'operator_id' => null,
            'operator_name' => null,
            'escalation_reason' => null,
            'location' => ['address' => $payload['location']['address'] ?? $site?->address ?? $dispatch->incident_location] + $position,
            'instructions' => $payload['instructions'] ?? null,
            'job_notes' => $payload['job_notes'] ?? null,
            'idempotency_key' => "patriot:{$dispatch->tenant_id}:dispatch:{$dispatch->patriot_job_id}",
        ];
    }

    private function cleanText(mixed $value, int $limit): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Str::limit(trim($value), $limit);
    }

    private function dispatchTime(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})\z/', $value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->utc()->toIso8601String();
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /** @return array{latitude: ?float, longitude: ?float} */
    private function position(mixed $lat, mixed $lng): array
    {
        if (! is_numeric($lat) || ! is_numeric($lng) || abs((float) $lat) > 90 || abs((float) $lng) > 180) {
            return ['latitude' => null, 'longitude' => null];
        }

        return ['latitude' => (float) $lat, 'longitude' => (float) $lng];
    }
}
