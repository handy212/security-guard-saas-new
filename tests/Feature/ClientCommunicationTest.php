<?php

namespace Tests\Feature;

use App\Livewire\Billing\EstimateShow;
use App\Livewire\Billing\InvoiceShow;
use App\Livewire\Guards\GuardProfile;
use App\Models\Estimate;
use App\Models\Guard;
use App\Models\GuardSiteAssignment;
use App\Models\Invoice;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ClientCommunicationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@demo.test')->firstOrFail();
        app()->instance('currentTenant', $this->admin->tenant);
        setPermissionsTeamId($this->admin->tenant_id);
        config(['services.evolution' => [
            'tenant_id' => $this->admin->tenant_id,
            'url' => 'https://evolution.example.test',
            'instance' => 'guardcore',
            'key' => 'test-key',
        ]]);
        Http::preventStrayRequests();
        Http::fake(['https://evolution.example.test/*' => Http::response(['key' => ['id' => 'test-submission']], 201)]);
    }

    public function test_billing_actions_send_the_clients_pdf_as_a_private_attachment(): void
    {
        Storage::fake();
        foreach ([Invoice::firstOrFail(), Estimate::firstOrFail()] as $document) {
            $document->update(['status' => 'draft']);
            $document->clientAccount->update(['phone' => '+233 24 123 4567']);
            $invoice = $document instanceof Invoice;
            Livewire::actingAs($this->admin)->test($invoice ? InvoiceShow::class : EstimateShow::class, [
                $invoice ? 'invoice' : 'estimate' => $document,
            ])->assertSee('Send via WhatsApp')->call('sendWhatsApp')->assertHasNoErrors();
            $this->assertSame('sent', $document->fresh()->status);
        }
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://evolution.example.test/message/sendMedia/guardcore'
            && $request->hasHeader('apikey', 'test-key')
            && $request['number'] === '233241234567'
            && $request['mimetype'] === 'application/pdf'
            && str_starts_with(base64_decode($request['media']), '%PDF'));
    }

    public function test_guard_posting_uses_the_site_client_phone_and_omits_private_guard_details(): void
    {
        $guard = Guard::firstOrFail();
        $site = Site::firstOrFail();
        $site->clientAccount->update(['phone' => '+233241234567']);
        $guard->update(['phone' => '+233999999999', 'rank' => 'Supervisor']);
        $assignment = GuardSiteAssignment::firstOrCreate([
            'tenant_id' => $guard->tenant_id, 'guard_id' => $guard->id, 'site_id' => $site->id,
        ]);

        Livewire::actingAs($this->admin)->test(GuardProfile::class, ['guard' => $guard])
            ->call('notifyPosting', $assignment->id, 'whatsapp')->assertHasNoErrors();

        Http::assertSent(fn (Request $request) => $request->url() === 'https://evolution.example.test/message/sendText/guardcore'
            && $request['number'] === '233241234567'
            && str_contains($request['text'], $guard->full_name.' (Supervisor)')
            && str_contains($request['text'], $site->name)
            && ! str_contains($request['text'], $guard->phone));
    }

    public function test_failed_provider_response_leaves_a_draft_invoice_unsent(): void
    {
        Storage::fake();
        $invoice = Invoice::firstOrFail();
        $invoice->update(['status' => 'draft', 'sent_at' => null]);
        $invoice->clientAccount->update(['phone' => '+233241234567']);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['https://evolution.example.test/*' => Http::response(['error' => 'provider detail'], 500)]);
        Livewire::actingAs($this->admin)->test(InvoiceShow::class, ['invoice' => $invoice])
            ->call('sendWhatsApp')->assertHasErrors('clientCommunication')->assertDontSee('provider detail');
        $this->assertSame('draft', $invoice->fresh()->status);
        $this->assertNull($invoice->fresh()->sent_at);
    }

    public function test_posting_email_is_addressed_to_the_client_and_does_not_call_whatsapp(): void
    {
        $guard = Guard::firstOrFail();
        $site = Site::firstOrFail();
        $site->clientAccount->update(['email' => 'posting@example.test']);
        $assignment = GuardSiteAssignment::firstOrCreate([
            'tenant_id' => $guard->tenant_id, 'guard_id' => $guard->id, 'site_id' => $site->id,
        ]);
        $messages = [];
        Event::listen(MessageSending::class, function ($event) use (&$messages): void {
            $messages[] = $event->message;
        });

        Livewire::actingAs($this->admin)->test(GuardProfile::class, ['guard' => $guard])
            ->call('notifyPosting', $assignment->id, 'email')->assertHasNoErrors();

        $this->assertCount(1, $messages);
        $this->assertSame('posting@example.test', $messages[0]->getTo()[0]->getAddress());
        $this->assertStringContainsString($guard->full_name, $messages[0]->getTextBody());
        Http::assertNothingSent();
    }

    public function test_whatsapp_is_available_only_to_the_configured_tenant(): void
    {
        $invoice = Invoice::firstOrFail();
        $invoice->update(['status' => 'draft']);
        $invoice->clientAccount->update(['phone' => '+233241234567']);
        config(['services.evolution.tenant_id' => $this->admin->tenant_id + 1]);

        Livewire::actingAs($this->admin)->test(InvoiceShow::class, ['invoice' => $invoice])
            ->assertDontSee('Send via WhatsApp')->call('sendWhatsApp')->assertStatus(404);
        Http::assertNothingSent();
    }
}
