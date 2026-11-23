<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PlatformBrandingService;
use App\Services\TenantFileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlatformBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_publish_and_restore_app_branding(): void
    {
        $this->seed();
        $platformAdmin = User::where('email', 'platform@guardops.test')->firstOrFail();
        Storage::fake(app(TenantFileStorageService::class)->diskName());

        $this->actingAs($platformAdmin)
            ->get(route('saas.branding'))
            ->assertOk()
            ->assertSee('App Branding');

        $this->post(route('saas.branding.update'), [
            'name' => 'Example Operations',
            'sign_in_message' => 'Use your company account to sign in.',
            'support_email' => 'help@example.test',
            'light_accent' => '#b91c1c',
            'dark_accent' => '#ef4444',
            'logo' => UploadedFile::fake()->image('logo.png', 100, 60),
            'favicon' => UploadedFile::fake()->image('favicon.png', 865, 289),
            'background' => UploadedFile::fake()->image('facility.png', 1200, 800),
        ])->assertSessionHasNoErrors()->assertRedirect(route('saas.branding'))
            ->assertSessionHas('branding_status', 'App branding saved.');

        $response = $this->get(route('saas.branding'))->assertOk()->assertDontSee('Saved successfully');
        $this->assertSame(1, substr_count($response->getContent(), 'App branding saved.'));

        $branding = app(PlatformBrandingService::class);
        $logo = $branding->logoUrl();
        $favicon = $branding->faviconUrl();
        $background = $branding->backgroundUrl();
        $image = getimagesizefromstring(app(TenantFileStorageService::class)->disk()->get($branding->current()['favicon']));
        $this->assertSame([192, 192], array_slice($image, 0, 2));
        $this->assertNotNull($logo);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Example Operations')
            ->assertSee('Use your company account to sign in.')
            ->assertSee('mailto:help@example.test')
            ->assertSee('--tenant-accent: #b91c1c;')
            ->assertSee('--tenant-dark-accent: #ef4444;')
            ->assertSee($logo)
            ->assertSee($favicon)
            ->assertSee($background);

        $this->get($background)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get($logo)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get($favicon)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($platformAdmin)->post(route('saas.branding.update'), [
            'remove_logo' => '1',
            'remove_favicon' => '1',
            'remove_background' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame(config('app.name'), $branding->name());
        $this->assertNull($branding->logoUrl());
        $this->assertSame(asset('icons/icon-192.png'), $branding->faviconUrl());
        $this->assertSame(asset('icons/sign-in-background.webp'), $branding->backgroundUrl());
        $this->get($background)->assertNotFound();
        $this->get($logo)->assertNotFound();
    }

    public function test_company_admin_cannot_edit_platform_branding(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();

        $this->actingAs($admin)->get(route('saas.branding'))->assertForbidden();
        $this->post(route('saas.branding.update'))->assertForbidden();
    }

    public function test_platform_toggle_switches_the_tenant_dashboard_and_can_be_restored(): void
    {
        $this->seed();
        $platform = User::where('email', 'platform@guardops.test')->firstOrFail();
        $tenantAdmin = User::where('email', 'admin@demo.test')->firstOrFail();

        $this->actingAs($platform)->post(route('saas.branding.update'), ['command_dashboard' => '1'])
            ->assertRedirect(route('saas.branding'));

        $this->actingAs($tenantAdmin)->get(route('dashboard'))
            ->assertOk()->assertSee('command-center', false)->assertSee('Site coverage')->assertSee('Deployment wizard');

        $this->travel(2)->hours();
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="command-center-map"', false)
            ->assertSee('No guard locations received in the last hour.');
        $this->travelBack();

        $this->get(route('guards.index'))
            ->assertOk()
            ->assertSee('command-navigation', false)
            ->assertSee('title="Guards" aria-label="Guards"', false)
            ->assertSee('command-sidebar-more', false)
            ->assertDontSee('class="command-center"', false);

        $this->get(route('settings.index'))
            ->assertOk()
            ->assertSee('command-navigation', false)
            ->assertSee('command-sidebar-secondary', false);

        $this->actingAs($platform)->get(route('saas.branding'))
            ->assertOk()
            ->assertDontSee('command-navigation', false);

        $this->actingAs($platform)->post(route('saas.branding.update'), ['command_dashboard' => '0'])
            ->assertRedirect(route('saas.branding'));

        $this->actingAs($tenantAdmin)->get(route('dashboard'))
            ->assertOk()->assertDontSee('class="command-center"', false)->assertSee('Coverage & response');

        $this->get(route('guards.index'))
            ->assertOk()
            ->assertDontSee('command-navigation', false);
    }
}
