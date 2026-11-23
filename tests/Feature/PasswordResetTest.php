<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_reset_their_password_with_a_single_use_email_link(): void
    {
        config(['app.url' => 'https://guardcore.test']);
        Notification::fake();
        $user = User::factory()->create(['status' => 'active', 'remember_token' => 'initial-token']);

        $this->get(route('login'))->assertOk()->assertSee('Forgot password?');
        $this->get(route('password.request'))->assertOk()->assertSee('Send reset link');

        $requestUrl = 'https://alternate.test/forgot-password';
        $this->from($requestUrl)
            ->post($requestUrl, ['email' => $user->email])
            ->assertRedirect($requestUrl)
            ->assertSessionHas('status');

        $notification = Notification::sent($user, ResetPassword::class)->sole();
        $url = $notification->toMail($user)->actionUrl;
        $this->assertStringStartsWith('https://guardcore.test/reset-password/', $url);

        $this->get($url)
            ->assertOk()
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertSee('Choose a new password');

        $updateUrl = route('password.update', ['token' => $notification->token]);
        $credentials = [
            'email' => $user->email,
            'password' => 'a new test password',
            'password_confirmation' => 'a new test password',
        ];

        $this->post($updateUrl, $credentials)
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Password updated. Sign in with your new password.');

        $user->refresh();
        $this->assertTrue(Hash::check($credentials['password'], $user->password));
        $this->assertNotSame('initial-token', $user->remember_token);
        $this->assertGuest();

        $this->post($updateUrl, $credentials)->assertSessionHasErrors('email');
    }

    public function test_reset_request_uses_the_same_message_for_unknown_and_inactive_accounts(): void
    {
        Notification::fake();
        $inactiveUser = User::factory()->create(['status' => 'inactive']);

        foreach (['missing@example.test', $inactiveUser->email] as $email) {
            $this->post(route('password.email'), ['email' => $email])
                ->assertSessionHas('status', 'If an active account matches that email, we’ll send a password reset link.');
        }

        Notification::assertNothingSent();
    }
}
