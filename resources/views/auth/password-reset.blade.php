@php
    $formEmail = old('email', $email ?? '');
@endphp
@component('layouts.guest')
    <x-slot:title>{{ isset($token) ? 'Choose a new password' : 'Reset your password' }}</x-slot:title>
    <main class="mx-auto grid min-h-dvh max-w-lg items-center px-4 py-8 sm:px-8">
        <section aria-labelledby="password-reset-heading" class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-8">
            <x-brand-mark />
            <h1 id="password-reset-heading" class="mt-4 text-2xl font-semibold tracking-tight">
                {{ isset($token) ? 'Choose a new password' : 'Reset your password' }}
            </h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                {{ isset($token) ? 'Use at least 12 characters.' : 'Enter your work email to receive a reset link.' }}
            </p>

            @if (session('status'))
                <p role="status" class="mt-4 rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-sm dark:border-zinc-700 dark:bg-zinc-800">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ isset($token) ? route('password.update', ['token' => $token]) : route('password.email') }}" class="mt-6 space-y-4">
                @csrf
                <x-input id="reset-email" label="Email" type="email" name="email" :value="is_string($formEmail) ? $formEmail : ''" :error="$errors->first('email')" autocomplete="username" required />

                @isset($token)
                    <x-input id="reset-password" label="New password" type="password" name="password" :error="$errors->first('password')" autocomplete="new-password" minlength="12" required />
                    <x-input id="reset-password-confirmation" label="Confirm new password" type="password" name="password_confirmation" autocomplete="new-password" minlength="12" required />
                @endisset

                <button type="submit" class="btn-primary min-h-11 w-full">
                    {{ isset($token) ? 'Update password' : 'Send reset link' }}
                </button>
            </form>

            <div class="mt-5 flex flex-wrap gap-x-4 gap-y-2">
                <a href="{{ route('login') }}" class="page-link">Back to sign in</a>
                @isset($token)
                    <a href="{{ route('password.request') }}" class="page-link">Request a new link</a>
                @endisset
                @if ($supportEmail = $platformBranding->current()['support_email'])
                    <a href="mailto:{{ $supportEmail }}" class="page-link">Contact support</a>
                @endif
            </div>
        </section>
    </main>
@endcomponent
