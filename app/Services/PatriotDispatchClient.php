<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PatriotDispatchClient
{
    /** @return list<object> */
    public function pendingJobs(): array
    {
        try {
            $response = $this->authenticatedRequest()->get('/api/v3/patrolResponse/jobs');
        } catch (ConnectionException) {
            throw new RuntimeException('Could not connect to the configured Patriot server.');
        }

        if (! $response->ok()) {
            throw new RuntimeException("Patriot authentication succeeded, but reading patrol response jobs failed (HTTP {$response->status()}).");
        }

        $jobs = json_decode($response->body());
        if (! is_array($jobs) || collect($jobs)->contains(fn (mixed $job): bool => ! is_int($job->jobID ?? null))) {
            throw new RuntimeException('Patriot returned an unexpected patrol response jobs payload.');
        }

        return $jobs;
    }

    public function checkWriteback(int $jobId, string $action, bool $apply, ?string $outcome = null, bool $reconcile = false): bool
    {
        $expectedStatus = match ($action) {
            'accept', 'decline' => 'DispatchRequest',
            'on-site' => 'DispatchAccepted',
            'complete' => 'OnSite',
            default => throw new RuntimeException('Choose accept, decline, on-site, or complete.'),
        };

        try {
            $patriot = $this->authenticatedRequest();
            $path = '/api/v3/patrolResponse/jobs/'.$jobId;
            $job = $patriot->get($path);
            if (! $job->ok()) {
                throw new RuntimeException("Patriot job lookup failed (HTTP {$job->status()}).");
            }
            if ($reconcile && $job->json('jobID') === $jobId && in_array($job->json('status'), match ($action) {
                'accept' => ['DispatchAccepted', 'OnSite'],
                'on-site' => ['OnSite'],
                'complete' => ['Attended'],
                default => [],
            }, true)) {
                return true;
            }
            if ($reconcile) {
                throw new RuntimeException('Patriot could not confirm the requested change. Verify the job in Patriot before retrying.');
            }
            if ($job->json('jobID') !== $jobId || $job->json('status') !== $expectedStatus) {
                throw new RuntimeException("The specified Patriot job must be in {$expectedStatus} for this action.");
            }
            if (! $apply) {
                return false;
            }

            $timestamp = now()->utc()->toIso8601String();
            $response = match ($action) {
                'decline' => $patriot->withBody('""', 'application/json')->post($path.'/release?failIfLastBroadcastPatrol=false'),
                'accept' => $patriot->withBody('""', 'application/json')->post($path.'/accept'),
                'on-site' => $patriot->post($path.'/update', [['kind' => 'OnSite', 'dateTime' => $timestamp]]),
                'complete' => $patriot->post($path.'/update', [
                    ['kind' => 'OffSite', 'dateTime' => $timestamp],
                    ['kind' => 'JobCompleted', 'dateTime' => $timestamp] + ($outcome ? ['note' => $outcome] : []),
                ]),
            };
        } catch (ConnectionException) {
            throw new RuntimeException('Patriot connection failed. Check the job in Patriot before retrying.');
        }

        if (! $response->successful()) {
            throw new RuntimeException("Patriot write-back returned HTTP {$response->status()}. Check the job in Patriot before retrying.");
        }
        if (in_array($action, ['on-site', 'complete'], true) && ! in_array(trim($response->body()), ['{}', '[]'], true)) {
            throw new RuntimeException('Patriot returned update details or warnings. Verify the result in Patriot before retrying.');
        }

        return true;
    }

    private function authenticatedRequest(): PendingRequest
    {
        $url = rtrim((string) config('services.patriot.url'), '/');
        $username = (string) config('services.patriot.username');
        $password = (string) config('services.patriot.password');

        if ($url === '' || trim($username) === '' || $password === '') {
            throw new RuntimeException('Configure PATRIOT_URL, PATRIOT_USERNAME, and PATRIOT_PASSWORD before running the check.');
        }

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('~\Ahttps://[^/?#@]+\z~i', $url)) {
            throw new RuntimeException('PATRIOT_URL must be the HTTPS origin of the Patriot server.');
        }

        $patriot = Http::baseUrl($url)->acceptJson()->connectTimeout(5)->timeout(15)->withoutRedirecting();

        $authentication = (clone $patriot)->asForm()->post('/api/Account/Token', [
            'username' => $username,
            'password' => $password,
            'grant_type' => 'password',
        ]);

        if (! $authentication->ok()) {
            throw new RuntimeException("Patriot authentication failed (HTTP {$authentication->status()}).");
        }

        $token = $authentication->json('access_token');
        if (! is_string($token) || trim($token) === '') {
            throw new RuntimeException('Patriot did not return an access token.');
        }

        return $patriot->withToken($token);
    }
}
