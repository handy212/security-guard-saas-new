<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50">
<div class="flex min-h-screen items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="mb-6 text-center">
            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-900 text-sm font-bold text-white">G</div>
            <h1 class="mt-3 text-lg font-semibold text-zinc-900">Sign in to GuardOps</h1>
            <p class="mt-1 text-sm text-zinc-500">Security operations platform</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            @if ($errors->any())
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @elseif (session('status'))
                <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-3">
                @csrf
                <x-input label="Email" type="email" name="email" value="{{ old('email') }}" required autofocus />
                <x-input label="Password" type="password" name="password" required />
                <label class="flex items-center gap-2 text-xs text-zinc-600">
                    <input type="checkbox" name="remember" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500">
                    Remember me
                </label>
                <button type="submit" class="btn-primary w-full">Sign in</button>
            </form>

            @if(config('app.debug'))
            <div class="mt-4 space-y-2 rounded-md border border-dashed border-zinc-200 bg-zinc-50 p-3 text-xs text-zinc-600">
                <div>
                    <div class="font-medium text-zinc-800">SaaS platform admin</div>
                    <div class="mt-1 font-mono text-[11px]">platform@guardops.test / password</div>
                    <p class="mt-1 text-zinc-500">Opens tenant management after sign in.</p>
                </div>
                <div class="border-t border-zinc-200 pt-2">
                    <div class="font-medium text-zinc-800">Tenant company admin</div>
                    <div class="mt-1 font-mono text-[11px]">admin@demo.test / password</div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>
