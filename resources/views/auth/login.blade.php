<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white">
<div class="grid min-h-screen lg:grid-cols-2">
  <div class="relative hidden overflow-hidden bg-gradient-to-br from-brand-900 via-slate-900 to-slate-950 p-12 lg:flex lg:flex-col lg:justify-between">
    <div>
      <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-lg font-black backdrop-blur">G</div>
      <h1 class="mt-8 text-4xl font-black tracking-tight">GuardOps SaaS</h1>
      <p class="mt-3 max-w-md text-lg text-slate-300">Enterprise security guard management — scheduling, patrols, dispatch, and client proof of service in one platform.</p>
    </div>
    <ul class="space-y-3 text-sm text-slate-400">
      <li class="flex items-center gap-2"><span class="text-brand-400">✓</span> Multi-tenant operations dashboard</li>
      <li class="flex items-center gap-2"><span class="text-brand-400">✓</span> Guard field app with offline patrol sync</li>
      <li class="flex items-center gap-2"><span class="text-brand-400">✓</span> Live dispatch, SOS, and client portal</li>
    </ul>
  </div>

  <div class="flex items-center justify-center p-6 sm:p-10">
    <div class="w-full max-w-md">
      <div class="mb-8 lg:hidden">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 font-black">G</div>
        <h2 class="mt-4 text-2xl font-bold">Sign in</h2>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-8 text-slate-900 shadow-xl shadow-slate-900/10">
        <h2 class="hidden text-xl font-bold lg:block">Welcome back</h2>
        <p class="mt-1 text-sm text-slate-500">Sign in to your security operations account</p>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <x-input label="Email" type="email" name="email" value="{{ old('email') }}" required autofocus />
            <x-input label="Password" type="password" name="password" required />
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                Remember me
            </label>
            <button type="submit" class="btn-primary w-full py-2.5">Sign in</button>
        </form>

        <div class="mt-6 rounded-lg border border-dashed border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">
            <div class="font-semibold text-slate-800">Demo credentials</div>
            <div class="mt-2 space-y-1 font-mono">
                <div>Admin: admin@demo.test / password</div>
                <div>Guard: john.guard@test / password</div>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
