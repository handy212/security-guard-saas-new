<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $portalTenantName ?? 'Client Portal' }} — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900" x-data="{ navOpen: false }">
<header class="border-b border-zinc-200 bg-white">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
        <div class="flex min-w-0 items-center gap-2">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-zinc-900 text-xs font-bold text-white">{{ strtoupper(substr($portalTenantName ?? 'C', 0, 1)) }}</div>
            <div class="min-w-0">
                <div class="truncate text-sm font-semibold">{{ $portalTenantName ?? 'Client Portal' }}</div>
                <div class="truncate text-[11px] text-zinc-500">Proof of service</div>
            </div>
        </div>
        <button type="button" @click="navOpen = !navOpen" class="rounded-md p-2 text-zinc-600 hover:bg-zinc-100 sm:hidden" aria-label="Menu">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <nav class="hidden items-center gap-1 text-sm sm:flex">
            <a href="{{ route('client-portal.dashboard') }}" class="rounded-md px-2.5 py-1.5 font-medium {{ request()->routeIs('client-portal.dashboard') ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-600 hover:bg-zinc-50' }}">Dashboard</a>
            <a href="{{ route('client-portal.approvals') }}" class="rounded-md px-2.5 py-1.5 font-medium {{ request()->routeIs('client-portal.approvals') ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-600 hover:bg-zinc-50' }}">Approvals</a>
            <form method="POST" action="{{ route('logout') }}" class="ml-1">
                @csrf
                <button type="submit" class="btn-secondary !px-2.5 !py-1.5 text-xs text-red-700">Sign out</button>
            </form>
        </nav>
    </div>
    <nav x-show="navOpen" x-cloak class="border-t border-zinc-100 px-4 py-2 sm:hidden">
        <a href="{{ route('client-portal.dashboard') }}" class="block rounded-md px-2 py-2 text-sm font-medium">Dashboard</a>
        <a href="{{ route('client-portal.approvals') }}" class="block rounded-md px-2 py-2 text-sm font-medium">Approvals</a>
        <form method="POST" action="{{ route('logout') }}" class="mt-1">
            @csrf
            <button type="submit" class="w-full rounded-md px-2 py-2 text-left text-sm text-red-700">Sign out</button>
        </form>
    </nav>
</header>
<main class="mx-auto max-w-6xl px-4 py-4 sm:px-6">{{ $slot }}</main>
@livewireScripts
</body>
</html>
