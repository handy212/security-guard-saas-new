<?php

namespace Tests\Feature;

use App\Livewire\Billing\InvoiceShow;
use App\Livewire\Settings\CompanyProfile;
use App\Models\ClientAccount;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\User;
use App\Services\CompanyProfileService;
use App\Services\EstimateService;
use App\Services\GuardIdCardRenderService;
use App\Services\PdfExportService;
use App\Services\TenantFileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_profile_and_logo_are_preserved_on_issued_documents(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        $tenant = $admin->tenant;
        app()->instance('currentTenant', $tenant);
        setPermissionsTeamId($tenant->id);
        $logoDisk = Storage::fake(app(TenantFileStorageService::class)->diskName());
        Storage::fake();

        $this->actingAs($admin)->get(route('settings.company-profile'))->assertOk();

        Livewire::actingAs($admin)->test(CompanyProfile::class)
            ->set('profile.name', 'Acme Security Ltd')
            ->set('profile.address', '20 Main Street')
            ->set('profile.tax_id', 'TAX-20')
            ->set('logo', UploadedFile::fake()->image('logo.png', 150, 80))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Company profile saved.');

        $logoPath = app(CompanyProfileService::class)->forTenant($tenant)['logo_path'];
        $this->assertNotNull($logoPath);
        $logoDisk->assertExists($logoPath);
        $card = app(GuardIdCardRenderService::class)->forSample($tenant);
        $this->assertSame('Acme Security Ltd', $card['brand']['company_name']);
        $this->assertStringStartsWith('data:image/png;base64,', $card['logoUrl']);

        $client = ClientAccount::where('tenant_id', $tenant->id)->firstOrFail();
        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'client_account_id' => $client->id,
            'invoice_number' => 'INV-ISSUER-TEST',
            'status' => 'draft',
        ]);
        $estimate = Estimate::create([
            'tenant_id' => $tenant->id,
            'client_account_id' => $client->id,
            'estimate_number' => 'EST-ISSUER-TEST',
            'estimate_date' => today(),
            'status' => 'draft',
        ]);

        Livewire::actingAs($admin)->test(InvoiceShow::class, ['invoice' => $invoice])
            ->assertSee('Acme Security Ltd')
            ->call('markSent')->assertHasNoErrors();
        app(EstimateService::class)->send($estimate);

        Livewire::actingAs($admin)->test(CompanyProfile::class)
            ->set('profile.name', 'Updated Security Ltd')
            ->set('removeLogo', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(app(CompanyProfileService::class)->forTenant($tenant)['logo_path']);

        foreach ([$invoice->fresh(), $estimate->fresh()] as $document) {
            $this->assertSame('Acme Security Ltd', $document->documentIssuer()['name']);
            $this->assertSame('TAX-20', $document->documentIssuer()['tax_id']);
            $this->assertSame($logoPath, $document->documentIssuer()['logo_path']);
        }

        $logoDisk->assertExists($logoPath);
        $path = app(PdfExportService::class)->exportInvoice($invoice->fresh());
        $this->assertStringStartsWith('%PDF', Storage::get($path));

        $nextDraft = $invoice->replicate(['issuer_profile', 'sent_at']);
        $nextDraft->fill(['invoice_number' => 'INV-NEXT', 'status' => 'draft'])->save();
        $this->assertSame('Updated Security Ltd', $nextDraft->documentIssuer()['name']);
    }

    public function test_company_profile_requires_settings_permission(): void
    {
        $this->seed();
        $guard = User::where('email', 'john.guard@test')->firstOrFail();

        $this->actingAs($guard)->get(route('settings.company-profile'))->assertForbidden();
    }
}
