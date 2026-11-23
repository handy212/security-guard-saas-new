<?php

namespace App\Console\Commands;

use App\Services\PatriotDispatchClient;
use Illuminate\Console\Command;
use RuntimeException;

class CheckPatriotWriteback extends Command
{
    protected $signature = 'guardops:patriot-writeback-check
        {job-id : Patriot test job ID}
        {action : accept, on-site, or complete}
        {--apply : Send the update to the designated Patriot test job}';

    protected $description = 'Preview or test a patrol response status update on one designated Patriot job';

    public function handle(PatriotDispatchClient $patriot): int
    {
        $jobId = filter_var($this->argument('job-id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
        $action = $this->argument('action');
        $apply = (bool) $this->option('apply');

        if ($jobId === false || ! in_array($action, ['accept', 'on-site', 'complete'], true)) {
            $this->error('Enter a positive Patriot job ID and choose accept, on-site, or complete.');

            return self::FAILURE;
        }

        if ($apply && (string) config('services.patriot.writeback_test_job_id') !== (string) $jobId) {
            $this->error('Set PATRIOT_WRITEBACK_TEST_JOB_ID to the designated test job before applying an update.');

            return self::FAILURE;
        }

        try {
            $changed = $patriot->checkWriteback($jobId, $action, $apply);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($changed
            ? "Patriot accepted the {$action} request for test job #{$jobId}."
            : "Preview only: Patriot job #{$jobId} is in the expected state for {$action}.");

        return self::SUCCESS;
    }
}
