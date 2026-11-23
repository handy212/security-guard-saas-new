<?php

namespace App\Services;

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
            'location' => ['address' => $address === '' ? null : $address] + $position,
            'instructions' => $this->cleanText($job->dispatchInstructions ?? null, 4000),
            'job_notes' => $this->cleanText($job->jobNotes ?? null, 4000),
        ];
    }

    /** @return array<string, mixed> */
    public function forDispatch(DispatchEvent $dispatch): array
    {
        $dispatch->loadMissing('site');
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
            'customer_name' => $payload['customer_name'] ?? null,
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
