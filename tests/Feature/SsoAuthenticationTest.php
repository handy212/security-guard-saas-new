<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Sso\OidcAuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sso_redirect_is_hidden_when_disabled(): void
    {
        Config::set('sso.enabled', false);

        $this->get('/auth/sso/redirect')->assertNotFound();
    }

    public function test_sso_redirect_starts_oidc_flow_when_enabled(): void
    {
        Config::set('sso.enabled', true);
        Config::set('sso.client_id', 'client-id');
        Config::set('sso.client_secret', 'client-secret');
        Config::set('sso.issuer', 'https://sso.example.com');
        Config::set('sso.redirect_uri', 'https://app.test/auth/sso/callback');

        Http::fake([
            'sso.example.com/.well-known/openid-configuration' => Http::response([
                'authorization_endpoint' => 'https://sso.example.com/oauth/authorize',
                'token_endpoint' => 'https://sso.example.com/oauth/token',
                'userinfo_endpoint' => 'https://sso.example.com/oauth/userinfo',
            ]),
        ]);

        $response = $this->get('/auth/sso/redirect');

        $response->assertRedirect();
        $this->assertStringContainsString('https://sso.example.com/oauth/authorize', $response->headers->get('Location'));
    }

    public function test_sso_callback_logs_in_existing_user(): void
    {
        $this->seed();

        Config::set('sso.enabled', true);
        Config::set('sso.client_id', 'client-id');
        Config::set('sso.client_secret', 'client-secret');
        Config::set('sso.issuer', 'https://sso.example.com');
        Config::set('sso.redirect_uri', 'https://app.test/auth/sso/callback');

        Http::fake([
            'sso.example.com/.well-known/openid-configuration' => Http::response([
                'authorization_endpoint' => 'https://sso.example.com/oauth/authorize',
                'token_endpoint' => 'https://sso.example.com/oauth/token',
                'userinfo_endpoint' => 'https://sso.example.com/oauth/userinfo',
            ]),
            'sso.example.com/oauth/token' => Http::response(['access_token' => 'token-123']),
            'sso.example.com/oauth/userinfo' => Http::response(['email' => 'admin@demo.test']),
        ]);

        $state = 'test-state';
        session(['sso_state' => $state, 'sso_nonce' => 'nonce']);

        $this->get('/auth/sso/callback?code=abc&state='.$state)
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs(User::where('email', 'admin@demo.test')->first());
    }
}
