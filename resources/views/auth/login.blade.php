<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in — {{ config('app.name', 'GuardCore Pro') }}</title>
    @include('partials.theme-init')
    @include('partials.brand-assets')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-100 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
<div class="relative flex min-h-screen">
    {{-- Brand panel --}}
    <div class="relative hidden w-[42%] overflow-hidden bg-zinc-950 lg:flex lg:flex-col lg:justify-between lg:p-10 xl:w-[46%] xl:p-12">
        <div class="absolute inset-0 opacity-[0.4]" style="background:
            radial-gradient(ellipse 80% 60% at 18% 18%, color-mix(in srgb, var(--tenant-brand) 55%, transparent), transparent 55%),
            radial-gradient(ellipse 70% 50% at 92% 82%, color-mix(in srgb, var(--tenant-brand) 22%, transparent), transparent 50%);"></div>
        <div class="absolute inset-0 opacity-[0.07]" style="background-image:
            linear-gradient(rgba(255,255,255,0.45) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.45) 1px, transparent 1px);
            background-size: 40px 40px;
            mask-image: radial-gradient(ellipse 75% 65% at 35% 40%, black, transparent);"></div>

        <div class="relative">
            <x-brand-mark size="lg" />
            <p class="mt-7 text-3xl font-semibold tracking-tight text-white xl:text-[2rem]">GuardCore Pro</p>
            <p class="mt-3 max-w-sm text-sm leading-relaxed text-zinc-400">
                Security operations platform for scheduling, dispatch, patrol, and proof of service.
            </p>
        </div>

        <div class="relative space-y-3">
            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-md border border-white/10 bg-white/5 px-2.5 py-1 text-[11px] font-medium text-zinc-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    Field sync ready
                </span>
                <span class="inline-flex items-center rounded-md border border-white/10 bg-white/5 px-2.5 py-1 text-[11px] font-medium text-zinc-300">
                    Tenant branded
                </span>
            </div>
            <p class="text-xs text-zinc-600">Command-room clarity for field teams.</p>
        </div>
    </div>

    {{-- Form --}}
    <div class="flex flex-1 flex-col justify-center px-4 py-10 sm:px-8">
        <div class="mx-auto w-full max-w-[22rem]">
            <div class="mb-8 lg:hidden">
                <x-brand-mark />
                <h1 class="mt-4 text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">GuardCore Pro</h1>
                <p class="mt-1 text-sm text-zinc-500">Sign in to continue</p>
            </div>

            <div class="hidden lg:block">
                <h1 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">Sign in</h1>
                <p class="mt-1 text-sm text-zinc-500">Welcome back to your operations workspace</p>
            </div>

            <div class="mt-8">
                @if ($errors->any())
                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200">
                        {{ $errors->first() }}
                    </div>
                @elseif (session('status'))
                    <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf
                    <x-input label="Email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
                    <x-input label="Password" type="password" name="password" required autocomplete="current-password" />
                    <label class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                        <input type="checkbox" name="remember" class="rounded border-zinc-300 text-accent-600 focus:ring-accent-600/20">
                        Remember me
                    </label>
                    <button type="submit" class="btn-primary w-full !py-2.5">Sign in</button>
                </form>

                @if(config('sso.enabled') && config('sso.client_id') && config('sso.issuer'))
                    <div class="mt-3">
                        <a href="{{ route('sso.redirect') }}" class="btn-secondary w-full !py-2.5">
                            Continue with SSO
                        </a>
                    </div>
                @endif

                <p class="mt-6 text-center text-xs text-zinc-500 dark:text-zinc-400">
                    Contact your administrator if you need access.
                </p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
