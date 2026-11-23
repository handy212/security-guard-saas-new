<?php

namespace App\Console\Commands;

use App\Services\PatriotDispatchClient;
use Illuminate\Console\Command;
use RuntimeException;

class CheckPatriotDispatchConnection extends Command
{
    protected $signature = 'guardops:patriot-check {--job-id= : Confirm that this Patriot patrol response job is visible}';

    protected $description = 'Check access to Patriot patrol response jobs without changing them';

    public function handle(PatriotDispatchClient $patriot): int
    {
        $jobId = $this->option('job-id');
        if ($jobId !== null && ! preg_match('/\A[1-9][0-9]*\z/', $jobId)) {
            $this->error('The job ID must be a positive integer.');

            return self::FAILURE;
        }

        try {
            $jobs = $patriot->pendingJobs();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Patriot authentication succeeded. Visible pending jobs: '.count($jobs).'.');

        if ($jobId !== null) {
            if (! collect($jobs)->contains(fn (object $job): bool => (string) $job->jobID === $jobId)) {
                $this->warn('The requested job is not visible to this Patriot account.');

                return self::FAILURE;
            }

            $this->info('The requested job is visible to this Patriot account.');
        }

        return self::SUCCESS;
    }
}
