@php
    $appName = $platformBranding->name();
    $organizationName = $tenantBranding['name'] ?? null;
    $identity = $platformBranding->current();
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in — {{ $appName }}@if ($organizationName) — {{ $organizationName }}@endif</title>
    @include('partials.theme-init')
    @include('partials.brand-assets')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-white font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
@include('partials.brand-colors')
<main class="grid min-h-dvh lg:grid-cols-[minmax(0,1fr)_minmax(27rem,36%)]">
    <div class="relative isolate flex min-h-44 items-start overflow-hidden bg-zinc-950 p-5 text-white sm:min-h-52 sm:p-8 lg:min-h-0 lg:p-10">
        <img src="{{ $platformBranding->backgroundUrl() }}" alt="" fetchpriority="high" class="pointer-events-none absolute inset-0 -z-20 size-full object-cover object-[left_35%] lg:object-[left_center]">
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 -z-10"
            style="background: linear-gradient(180deg, #09090bb8, transparent 42%), linear-gradient(0deg, color-mix(in srgb, var(--workspace-accent) 14%, transparent), transparent 42%)"
        ></div>

        @if (! $platformBranding->logoUrl() && $appName === 'GuardCore Pro')
            <img src="{{ asset('icons/guardcore-wordmark.png') }}" alt="{{ $appName }}" class="h-11 w-auto max-w-full rounded-lg bg-white px-2 object-contain sm:h-12">
        @else
            <div class="inline-flex max-w-full items-center gap-2.5 rounded-lg bg-zinc-950/80 px-2.5 py-2">
                <x-brand-mark />
                <span class="min-w-0 break-words text-sm font-semibold">{{ $appName }}</span>
            </div>
        @endif
    </div>

    <section aria-labelledby="sign-in-heading" class="flex min-w-0 items-center border-t border-zinc-200 px-5 py-7 sm:px-10 lg:border-t-0 lg:border-l lg:px-12 xl:px-16 dark:border-zinc-800">
        <div class="mx-auto w-full max-w-md">
            @if ($organizationName)
                <p class="mb-2 break-words text-sm font-semibold text-accent-700 dark:text-accent-300">{{ $organizationName }}</p>
            @endif
            <h1 id="sign-in-heading" class="text-3xl font-semibold tracking-tight sm:text-4xl">Sign in</h1>
            @if ($identity['sign_in_message'])
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $identity['sign_in_message'] }}</p>
            @endif

            @if ($errors->any())
                <p role="alert" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200">
                    {{ $errors->first() }}
                </p>
            @elseif (session('status'))
                <p role="status" class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">
                    {{ session('status') }}
                </p>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                @csrf
                <x-input id="login-email" label="Email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" />
                <x-input id="login-password" label="Password" type="password" name="password" required autocomplete="current-password" />

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <label class="inline-flex min-h-9 cursor-pointer items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="checkbox" name="remember" @checked(old('remember')) class="size-4 accent-accent-600">
                        Remember me
                    </label>
                    <button id="login-password-toggle" type="button" aria-controls="login-password" class="page-link min-h-9 px-1" hidden>Show password</button>
                </div>

                <button type="submit" class="btn-primary min-h-11 w-full">Sign in</button>
            </form>

            @if (config('sso.enabled') && config('sso.client_id') && config('sso.issuer'))
                <a href="{{ route('sso.redirect') }}" class="btn-secondary mt-3 min-h-11 w-full">Continue with SSO</a>
            @endif

            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-1 border-t border-zinc-200 pt-2 dark:border-zinc-800">
                <a href="{{ route('password.request') }}" class="page-link inline-flex min-h-9 items-center">Forgot password?</a>
                @if ($identity['support_email'])
                    <a href="mailto:{{ $identity['support_email'] }}" class="page-link inline-flex min-h-9 items-center">Contact support</a>
                @endif
            </div>
        </div>
    </section>
</main>
<script>
    (() => {
        const password = document.getElementById('login-password');
        const toggle = document.getElementById('login-password-toggle');

        toggle.hidden = false;
        toggle.addEventListener('click', () => {
            const show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            toggle.textContent = show ? 'Hide password' : 'Show password';
        });
    })();
</script>
</body>
</html>
