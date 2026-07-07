<?php

namespace Tests\Feature;

use App\Models\ClientAccount;
use App\Models\ClientReportSchedule;
use App\Services\ClientReportDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientReportDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_schedules_send_email_and_update_last_sent(): void
    {
        $this->seed();

        $client = ClientAccount::first();

        $schedule = ClientReportSchedule::create([
            'tenant_id' => $client->tenant_id,
            'client_account_id' => $client->id,
            'report_type' => 'daily_activity',
            'frequency' => 'weekly',
            'recipients' => ['reports@client.test'],
            'is_active' => true,
        ]);

        $delivered = app(ClientReportDeliveryService::class)->deliverDueSchedules();

        $this->assertSame(1, $delivered);
        $this->assertNotNull($schedule->fresh()->last_sent_at);
    }
}
