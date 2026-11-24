<?php

namespace Tests\Feature;

use App\Livewire\Clients\ClientProfile;
use App\Models\ClientAccount;
use App\Models\ClientReportSchedule;
use App\Models\User;
use App\Services\ClientReportDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
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

    public function test_whatsapp_schedule_uses_the_saved_client_number_and_is_sent_once_when_due(): void
    {
        $this->seed();
        $client = ClientAccount::firstOrFail();
        $client->update(['phone' => '+233 24 123 4567']);
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        setPermissionsTeamId($client->tenant_id);
        $this->configureWhatsApp($client->tenant_id);

        Livewire::actingAs($admin)->test(ClientProfile::class, ['clientAccount' => $client])
            ->set('activeTab', 'reports')
            ->set('reportForm.channel', 'whatsapp')
            ->call('addReportSchedule')->assertHasNoErrors();

        $schedule = $client->reportSchedules()->sole();
        $this->assertSame('whatsapp', $schedule->delivery_channel);
        $this->artisan('guardops:deliver-scheduled-client-reports')->assertSuccessful();
        $this->artisan('guardops:deliver-scheduled-client-reports')->assertSuccessful();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['number'] === '233241234567'
            && str_contains($request['text'], 'Sign in to the client portal'));
        $this->assertNotNull($schedule->fresh()->last_sent_at);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $client->tenant_id, 'action' => 'client.report_whatsapp_submitted']);
    }

    public function test_a_changed_client_phone_does_not_receive_an_existing_schedule(): void
    {
        $this->seed();
        $client = ClientAccount::firstOrFail();
        $this->configureWhatsApp($client->tenant_id);
        $client->update(['phone' => '+233241999999']);
        $schedule = $client->reportSchedules()->create([
            'tenant_id' => $client->tenant_id,
            'report_type' => 'daily_activity',
            'frequency' => 'weekly',
            'delivery_channel' => 'whatsapp',
            'whatsapp_phone' => '233241234567',
            'is_active' => true,
        ]);

        $this->artisan('guardops:deliver-scheduled-client-reports')->assertSuccessful();
        Http::assertNothingSent();
        $this->assertNull($schedule->fresh()->last_sent_at);
    }

    private function configureWhatsApp(int $tenantId): void
    {
        config(['services.evolution' => ['tenant_id' => $tenantId, 'url' => 'https://evolution.example.test', 'instance' => 'guardcore', 'key' => 'test-key']]);
        Http::preventStrayRequests();
        Http::fake(['https://evolution.example.test/*' => Http::response(['key' => ['id' => 'test-report']], 201)]);
    }
}
