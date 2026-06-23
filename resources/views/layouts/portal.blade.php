<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Portal — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-100 to-slate-200 text-slate-900">
<header class="border-b border-slate-200/80 bg-white/90 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 text-sm font-black text-white">C</div>
            <div>
                <div class="text-lg font-bold">Client Portal</div>
                <div class="text-xs text-slate-500">Proof of service & reports</div>
            </div>
        </div>
        <nav class="flex items-center gap-1 text-sm">
            <a href="{{ route('client-portal.dashboard') }}"
               class="rounded-lg px-3 py-2 font-medium {{ request()->routeIs('client-portal.dashboard') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100' }}">Dashboard</a>
            <a href="{{ route('client-portal.approvals') }}"
               class="rounded-lg px-3 py-2 font-medium {{ request()->routeIs('client-portal.approvals') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100' }}">Approvals</a>
            <form method="POST" action="{{ route('logout') }}" class="ml-2">
                @csrf
                <button type="submit" class="btn-secondary !px-3 !py-2 text-red-700">Sign out</button>
            </form>
        </nav>
    </div>
</header>
<main class="mx-auto max-w-6xl px-4 py-6 md:px-6">{{ $slot }}</main>
@livewireScripts
</body>
</html>
