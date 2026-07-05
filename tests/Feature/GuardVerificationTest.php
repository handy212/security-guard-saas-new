<?php

namespace Tests\Feature;

use App\Models\Guard;
use App\Models\GuardCertification;
use App\Models\GuardDocument;
use App\Models\GuardVerificationToken;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use App\Services\GuardIdCardPresenter;
use App\Services\GuardIdCardRenderService;
use App\Services\GuardVerificationService;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuardVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function tenantVerifyPath(GuardVerificationToken $token): string
    {
        $token->loadMissing('assignedGuard.tenant');
        $slug = $token->assignedGuard->tenant->slug;

        return '/g/'.$slug.'/'.$token->token;
    }

    public function test_public_verification_page_shows_safe_fields(): void
    {
        $this->seed();

        $guard = Guard::where('employee_number', 'G-001')->first();
        $token = $guard->activeVerificationToken();

        $this->assertNotNull($token);

        $response = $this->get($this->tenantVerifyPath($token));

        $response->assertOk()
            ->assertSee($guard->full_name)
            ->assertSee('Demo Security Company')
            ->assertSee('Senior Officer')
            ->assertSee('Verified by Demo Security Company')
            ->assertDontSee($guard->email)
            ->assertDontSee($guard->phone)
            ->assertDontSee('hourly');
    }

    public function test_revoked_token_returns_not_found(): void
    {
        $this->seed();

        $guard = Guard::where('employee_number', 'G-001')->first();
        $token = $guard->activeVerificationToken();
        $token->update(['revoked_at' => now()]);

        $this->get('/g/'.$token->token)->assertNotFound();
    }

    public function test_suspended_guard_returns_not_found(): void
    {
        $this->seed();

        $guard = Guard::where('employee_number', 'G-001')->first();
        $token = $guard->activeVerificationToken();
        $guard->update(['verification_status' => 'suspended']);

        $this->get('/g/'.$token->token)->assertNotFound();
    }

    public function test_regenerating_token_invalidates_previous_url(): void
    {
        $this->seed();

        $guard = Guard::where('employee_number', 'G-001')->first();
        $oldToken = $guard->activeVerificationToken()->token;

        app(GuardVerificationService::class)->rotateToken($guard);

        $this->get('/g/'.$oldToken)->assertNotFound();
        $this->get($this->tenantVerifyPath($guard->fresh()->activeVerificationToken()))->assertOk();
    }

    public function test_ensure_token_does_not_rotate_existing_qr(): void
    {
        $this->seed();

        $guard = Guard::where('employee_number', 'G-001')->first();
        $service = app(GuardVerificationService::class);
        $original = $guard->activeVerificationToken()->token;

        $service->ensureToken($guard);

        $this->assertSame($original, $guard->fresh()->activeVerificationToken()->token);
    }

    public function test_tenant_scoped_url_rejects_wrong_tenant_slug(): void
    {
        $this->seed();

        $guard = Guard::where('employee_number', 'G-001')->first();
        $token = $guard->activeVerificationToken();

        $this->get('/g/wrong-tenant/'.$token->token)->assertNotFound();
    }

    public function test_legacy_verification_url_still_works(): void
    {
        $this->seed();

        $guard = Guard::where('employee_number', 'G-001')->first();
        $token = $guard->activeVerificationToken();

        $this->get('/g/'.$token->token)->assertOk();
    }

    public function test_mark_verified_requires_checklist(): void
    {
        $this->seed();

        $tenant = Tenant::first();
        $admin = User::where('email', 'admin@demo.test')->first();

        $guard = Guard::create([
            'tenant_id' => $tenant->id,
            'employee_number' => 'G-099',
            'first_name' => 'Test',
            'last_name' => 'Guard',
            'status' => 'active',
            'verification_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('guards.show', $guard))
            ->assertOk();

        $service = app(GuardVerificationService::class);
        $this->assertFalse($service->vettingChecklist($guard)['ready']);

        Storage::fake('public');
        $guard->update(['photo_path' => 'tenants/1/guards/1/photo.jpg']);
        GuardDocument::create([
            'tenant_id' => $tenant->id,
            'guard_id' => $guard->id,
            'type' => 'id',
            'file_path' => 'tenants/1/guards/1/id.pdf',
            'status' => 'valid',
        ]);
        $guard->update(['license_number' => 'LIC-1', 'license_expires_at' => now()->addYear()]);
        GuardCertification::create([
            'tenant_id' => $tenant->id,
            'guard_id' => $guard->id,
            'name' => 'Basic Security',
            'expires_at' => now()->addYear(),
            'status' => 'valid',
        ]);

        $guard->refresh();
        $this->assertTrue($service->vettingChecklist($guard)['ready']);

        $service->markVerified($guard, $admin->id);

        $guard->refresh();
        $this->assertEquals('verified', $guard->verification_status);
        $this->assertNotNull($guard->activeVerificationToken());
    }

    public function test_mark_verified_shows_error_when_checklist_incomplete(): void
    {
        $this->seed();

        $tenant = Tenant::first();
        $admin = User::where('email', 'admin@demo.test')->first();

        $guard = Guard::create([
            'tenant_id' => $tenant->id,
            'employee_number' => 'G-098',
            'first_name' => 'Incomplete',
            'last_name' => 'Guard',
            'status' => 'active',
            'verification_status' => 'pending',
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Guards\GuardProfile::class, ['guard' => $guard])
            ->call('markVerified')
            ->assertHasErrors(['verification'])
            ->assertSee('Complete these requirements first');

        $guard->refresh();
        $this->assertEquals('pending', $guard->verification_status);
    }

    public function test_guard_profile_certification_crud(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->first();
        $guard = Guard::where('employee_number', 'G-001')->first();

        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Livewire\Guards\GuardProfile::class, ['guard' => $guard])
            ->set('activeTab', 'certifications')
            ->set('certForm.name', 'Fire Safety')
            ->set('certForm.issuer', 'NFPA')
            ->call('saveCertification')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('guard_certifications', [
            'guard_id' => $guard->id,
            'name' => 'Fire Safety',
        ]);
    }

    public function test_guard_id_card_print_page_opens_for_verified_guard(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->first();
        $guard = Guard::where('employee_number', 'G-001')->first();

        $this->actingAs($admin)
            ->get(route('guards.id-card.print', $guard))
            ->assertOk()
            ->assertSee('Print ID card')
            ->assertSee($guard->full_name);
    }

    public function test_guard_id_card_pdf_downloads_front_and_back(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->first();
        $guard = Guard::where('employee_number', 'G-001')->first();

        $response = $this->actingAs($admin)
            ->get(route('guards.id-card', $guard));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('%PDF', $content);
        $this->assertMatchesRegularExpression('/\/Count\s+[2-9]\b/', $content);
        $this->assertStringContainsString('/Subtype /Image', $content);
    }

    public function test_qr_png_generation_for_pdf(): void
    {
        $png = app(QrCodeService::class)->pngBase64('https://example.test/g/TESTTOKEN', 96);

        $this->assertNotEmpty($png);
        $this->assertNotEmpty(base64_decode($png, true));

        $path = app(QrCodeService::class)->pngFile('https://example.test/g/TESTTOKEN', 128);
        $this->assertNotNull($path);
        $this->assertFileExists($path);
        $this->assertStringStartsWith("\x89PNG", (string) file_get_contents($path));
        @unlink($path);
    }

    public function test_authenticated_user_can_download_guard_photo_and_document(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->first();
        $guard = Guard::where('employee_number', 'G-001')->first();

        Storage::fake('public');
        $photoPath = "tenants/{$guard->tenant_id}/guards/{$guard->id}/photos/test.png";
        Storage::disk('public')->put($photoPath, 'fake-image');
        $guard->update(['photo_path' => $photoPath]);

        $document = GuardDocument::create([
            'tenant_id' => $guard->tenant_id,
            'guard_id' => $guard->id,
            'type' => 'id',
            'file_path' => "tenants/{$guard->tenant_id}/guards/{$guard->id}/id.pdf",
            'status' => 'valid',
        ]);
        Storage::disk('public')->put($document->file_path, '%PDF-1.4 test');

        $this->actingAs($admin)
            ->get(route('files.guard-photo', $guard))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('files.guard-document', $document))
            ->assertOk();
    }

    public function test_verified_guard_without_token_cannot_download_id_card(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->first();
        $guard = Guard::where('employee_number', 'G-001')->first();

        $guard->verificationTokens()->update(['revoked_at' => now()]);

        $this->actingAs($admin)
            ->get(route('guards.id-card', $guard))
            ->assertForbidden();
    }

    public function test_id_card_eligibility_reflects_verification_state(): void
    {
        $this->seed();

        $verification = app(GuardVerificationService::class);
        $guard = Guard::where('employee_number', 'G-001')->first();

        $this->assertTrue($verification->idCardEligibility($guard)['can_download']);

        $guard->verificationTokens()->update(['revoked_at' => now()]);

        $eligibility = $verification->idCardEligibility($guard->fresh());
        $this->assertFalse($eligibility['can_download']);
        $this->assertSame('regenerate', $eligibility['action']);
    }

    public function test_unverified_guard_cannot_download_id_card(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->first();
        $tenant = Tenant::first();

        $guard = Guard::create([
            'tenant_id' => $tenant->id,
            'employee_number' => 'G-PENDING',
            'first_name' => 'Pending',
            'last_name' => 'Guard',
            'status' => 'active',
            'verification_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('guards.id-card', $guard))
            ->assertForbidden();
    }

    public function test_unverified_guard_token_does_not_resolve_on_public_scan(): void
    {
        $this->seed();

        $tenant = Tenant::first();
        $guard = Guard::create([
            'tenant_id' => $tenant->id,
            'employee_number' => 'G-UNVER',
            'first_name' => 'Unverified',
            'last_name' => 'Scan',
            'status' => 'active',
            'verification_status' => 'pending',
        ]);

        $token = \App\Models\GuardVerificationToken::create([
            'tenant_id' => $tenant->id,
            'guard_id' => $guard->id,
            'token' => 'BADTOKEN01',
        ]);

        $this->get('/g/'.$token->token)->assertNotFound();
    }

    public function test_expired_token_returns_not_found(): void
    {
        $this->seed();

        $guard = Guard::where('employee_number', 'G-001')->first();
        $token = $guard->activeVerificationToken();
        $token->update(['expires_at' => now()->subDay()]);

        $this->get('/g/'.$token->token)->assertNotFound();
    }

    public function test_public_photo_requires_valid_token(): void
    {
        $this->seed();

        $guard = Guard::where('employee_number', 'G-001')->first();
        $token = $guard->activeVerificationToken();

        Storage::fake('public');
        $photoPath = "tenants/{$guard->tenant_id}/guards/{$guard->id}/photos/test.png";
        Storage::disk('public')->put($photoPath, 'fake-image');
        $guard->update(['photo_path' => $photoPath]);
        $guard->load('tenant');

        $this->get(route('guard.verify.photo', ['tenant' => $guard->tenant->slug, 'token' => $token->token]))->assertOk();

        $this->get(route('guard.verify.photo.legacy', 'INVALIDTOKEN'))->assertNotFound();
    }

    public function test_rotate_token_blocked_for_unverified_guard(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->first();
        $tenant = Tenant::first();

        $guard = Guard::create([
            'tenant_id' => $tenant->id,
            'employee_number' => 'G-NOQR',
            'first_name' => 'No',
            'last_name' => 'QR',
            'status' => 'active',
            'verification_status' => 'pending',
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Guards\GuardProfile::class, ['guard' => $guard])
            ->call('rotateQrToken')
            ->assertHasErrors(['verification']);

        $this->assertNull($guard->fresh()->activeVerificationToken());
    }

    public function test_expired_certifications_hidden_on_public_page(): void
    {
        $this->seed();

        $guard = Guard::where('employee_number', 'G-001')->first();
        $token = $guard->activeVerificationToken();

        GuardCertification::create([
            'tenant_id' => $guard->tenant_id,
            'guard_id' => $guard->id,
            'name' => 'Expired Training',
            'expires_at' => now()->subMonth(),
            'status' => 'expired',
        ]);

        $this->get('/g/'.$token->token)
            ->assertOk()
            ->assertDontSee('Expired Training');
    }

    public function test_browser_pdf_view_data_embeds_qr_from_generated_file(): void
    {
        $this->seed();

        $guard = Guard::where('employee_number', 'G-001')->first();
        $renderer = app(GuardIdCardRenderService::class);

        $built = $renderer->forGuard($guard);
        $pdfData = $renderer->browserPdfViewData($built['viewData']);
        $renderer->cleanup($built['tempFiles']);

        $this->assertNotEmpty($pdfData['qrPng']);
        $this->assertNotEmpty(base64_decode($pdfData['qrPng'], true));
    }

    public function test_landscape_print_page_renders_horizontal_card(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->first();
        $guard = Guard::where('employee_number', 'G-001')->first();

        TenantSetting::updateOrCreate(
            ['tenant_id' => $guard->tenant_id, 'key' => 'id_card'],
            ['value' => array_merge(
                TenantSetting::query()
                    ->where('tenant_id', $guard->tenant_id)
                    ->where('key', 'id_card')
                    ->value('value') ?? [],
                ['orientation' => 'landscape']
            )]
        );

        $this->actingAs($admin)
            ->get(route('guards.id-card.print', $guard))
            ->assertOk()
            ->assertSee('orientation-landscape', false);
    }

    public function test_modern_preview_route_matches_shared_template(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->first();
        $guard = Guard::where('employee_number', 'G-001')->first();

        $this->actingAs($admin)
            ->get(route('guards.id-card.preview', $guard))
            ->assertOk()
            ->assertSee('id-card-preview', false)
            ->assertSee($guard->full_name);
    }

    public function test_public_verify_page_references_tenant_scoped_photo_url(): void
    {
        $this->seed();

        $guard = Guard::where('employee_number', 'G-001')->first();
        $token = $guard->activeVerificationToken();
        $guard->load('tenant');

        Storage::fake('public');
        $photoPath = "tenants/{$guard->tenant_id}/guards/{$guard->id}/photos/test.png";
        Storage::disk('public')->put($photoPath, 'fake-image');
        $guard->update(['photo_path' => $photoPath]);

        $photoUrl = route('guard.verify.photo', [
            'tenant' => $guard->tenant->slug,
            'token' => $token->token,
        ]);

        $this->get($this->tenantVerifyPath($token))
            ->assertOk()
            ->assertSee($photoUrl, false);
    }

    public function test_presenter_preview_scale_differs_by_orientation(): void
    {
        $presenter = app(GuardIdCardPresenter::class);

        $this->assertSame(1.0, $presenter->previewScale('portrait'));
        $this->assertSame(0.72, $presenter->previewScale('landscape'));
    }
}
