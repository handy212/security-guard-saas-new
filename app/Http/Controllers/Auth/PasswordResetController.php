<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('auth.password-reset');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email', 'max:255']]);

        Password::sendResetLink($credentials + ['status' => 'active']);

        return back()->with('status', 'If an active account matches that email, we’ll send a password reset link.');
    }

    public function edit(Request $request, string $token): Response
    {
        $email = $request->query('email');

        return response()->view('auth.password-reset', [
            'token' => $token,
            'email' => is_string($email) ? $email : '',
        ])->header('Referrer-Policy', 'no-referrer');
    }

    public function update(Request $request, string $token): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(12)],
        ]);

        $status = Password::reset($credentials + ['token' => $token, 'status' => 'active'], function (User $user, string $password): void {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'This reset link is invalid or has expired. Request a new link to continue.']);
        }

        return redirect()->route('login')->with('status', 'Password updated. Sign in with your new password.');
    }
}
