<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PatriotDispatchConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.patriot' => [
            'url' => 'https://patriot.example.test',
            'username' => 'test-patrol',
            'password' => 'test-password',
        ]]);

        Http::preventStrayRequests();
    }

    public function test_it_confirms_a_visible_job_using_only_authentication_and_a_read(): void
    {
        Http::fake([
            'https://patriot.example.test/api/Account/Token' => Http::response(['access_token' => 'test-token']),
            'https://patriot.example.test/api/v3/patrolResponse/jobs' => Http::response([['jobID' => 42]]),
        ]);

        $this->artisan('guardops:patriot-check', ['--job-id' => '42'])
            ->expectsOutput('Patriot authentication succeeded. Visible pending jobs: 1.')
            ->expectsOutput('The requested job is visible to this Patriot account.')
            ->assertSuccessful();

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://patriot.example.test/api/Account/Token'
            && $request->data() === ['username' => 'test-patrol', 'password' => 'test-password', 'grant_type' => 'password']);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://patriot.example.test/api/v3/patrolResponse/jobs'
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_writeback_preview_reads_the_job_without_sending_an_update(): void
    {
        Http::fake([
            'https://patriot.example.test/api/Account/Token' => Http::response(['access_token' => 'test-token']),
            'https://patriot.example.test/api/v3/patrolResponse/jobs/42' => Http::response(['jobID' => 42, 'status' => 'DispatchRequest']),
        ]);

        $this->artisan('guardops:patriot-writeback-check', ['job-id' => '42', 'action' => 'accept'])
            ->expectsOutput('Preview only: Patriot job #42 is in the expected state for accept.')
            ->assertSuccessful();

        Http::assertSentCount(2);
    }

    public function test_writeback_refuses_to_apply_to_an_undesignated_job(): void
    {
        config(['services.patriot.writeback_test_job_id' => 41]);
        Http::fake();

        $this->artisan('guardops:patriot-writeback-check', ['job-id' => '42', 'action' => 'accept', '--apply' => true])
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_writeback_accepts_the_designated_job_using_the_documented_json_string(): void
    {
        config(['services.patriot.writeback_test_job_id' => 42]);
        Http::fake([
            'https://patriot.example.test/api/Account/Token' => Http::response(['access_token' => 'test-token']),
            'https://patriot.example.test/api/v3/patrolResponse/jobs/42' => Http::response(['jobID' => 42, 'status' => 'DispatchRequest']),
            'https://patriot.example.test/api/v3/patrolResponse/jobs/42/accept' => Http::response(),
        ]);

        $this->artisan('guardops:patriot-writeback-check', ['job-id' => '42', 'action' => 'accept', '--apply' => true])
            ->assertSuccessful();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_ends_with($request->url(), '/jobs/42/accept')
            && $request->body() === '""'
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_it_reports_when_the_requested_job_is_not_visible(): void
    {
        Http::fake([
            'https://patriot.example.test/api/Account/Token' => Http::response(['access_token' => 'test-token']),
            'https://patriot.example.test/api/v3/patrolResponse/jobs' => Http::response([]),
        ]);

        $this->artisan('guardops:patriot-check', ['--job-id' => '42'])
            ->expectsOutput('Patriot authentication succeeded. Visible pending jobs: 0.')
            ->expectsOutput('The requested job is not visible to this Patriot account.')
            ->assertFailed();
    }

    public function test_it_does_not_read_jobs_when_authentication_fails(): void
    {
        Http::fake([
            'https://patriot.example.test/api/Account/Token' => Http::response(['error_description' => 'sensitive response detail'], 400),
        ]);

        $this->artisan('guardops:patriot-check')
            ->expectsOutput('Patriot authentication failed (HTTP 400).')
            ->doesntExpectOutput('sensitive response detail')
            ->assertFailed();

        Http::assertSentCount(1);
    }
}
