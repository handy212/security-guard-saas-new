<?php

namespace App\Services\Sso;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OidcAuthenticationService
{
    public function isEnabled(): bool
    {
        return (bool) config('sso.enabled')
            && filled(config('sso.client_id'))
            && filled(config('sso.client_secret'))
            && filled(config('sso.issuer'));
    }

    public function authorizationRedirectUrl(): string
    {
        $config = $this->discover();

        $state = Str::random(40);
        session([
            'sso_state' => $state,
            'sso_nonce' => Str::random(40),
        ]);

        $query = http_build_query([
            'client_id' => config('sso.client_id'),
            'redirect_uri' => config('sso.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'nonce' => session('sso_nonce'),
        ]);

        return rtrim($config['authorization_endpoint'], '?').'?'.$query;
    }

    public function authenticateFromCallback(string $code, string $state): User
    {
        abort_unless($state === session('sso_state'), 403, 'Invalid SSO state.');
        session()->forget('sso_state');

        $config = $this->discover();

        $tokenResponse = Http::asForm()->post($config['token_endpoint'], [
            'grant_type' => 'authorization_code',
            'client_id' => config('sso.client_id'),
            'client_secret' => config('sso.client_secret'),
            'redirect_uri' => config('sso.redirect_uri'),
            'code' => $code,
        ]);

        if (! $tokenResponse->successful()) {
            throw new RuntimeException('SSO token exchange failed.');
        }

        $accessToken = $tokenResponse->json('access_token');

        $userInfo = Http::withToken($accessToken)->get($config['userinfo_endpoint']);

        if (! $userInfo->successful()) {
            throw new RuntimeException('SSO userinfo request failed.');
        }

        $email = $userInfo->json('email');

        if (! $email) {
            throw new RuntimeException('SSO account is missing an email address.');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            throw new RuntimeException('No GuardOps account exists for this SSO identity.');
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }

    private function discover(): array
    {
        $issuer = rtrim((string) config('sso.issuer'), '/');
        $response = Http::get($issuer.'/.well-known/openid-configuration');

        if (! $response->successful()) {
            throw new RuntimeException('Unable to load SSO provider configuration.');
        }

        return $response->json();
    }
}
