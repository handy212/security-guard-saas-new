<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(config('notifications.push.vapid.public_key'))
        <meta name="vapid-public-key" content="{{ config('notifications.push.vapid.public_key') }}">
    @endif
    <title>{{ ($tenantBranding['name'] ?? $portalTenantName ?? 'Client Portal') }} — {{ config('app.name', 'GuardCore Pro') }}</title>
    @include('partials.theme-init')
    @include('partials.brand-assets')
    <link rel="manifest" href="/manifest-client.json">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100" x-data="{ navOpen: false }">
<header class="sticky top-0 z-20 border-b border-zinc-200/80 bg-white/95 backdrop-blur-sm dark:border-zinc-800 dark:bg-zinc-900/95">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
        <div class="flex min-w-0 items-center gap-2.5">
            <x-brand-mark />
            <div class="min-w-0">
                <div class="truncate text-sm font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $tenantBranding['name'] ?? $portalTenantName ?? 'Client Portal' }}</div>
                <div class="truncate text-[11px] text-zinc-500 dark:text-zinc-400">{{ $tenantBranding['tagline'] ?? 'Proof of service' }}</div>
            </div>
        </div>
        <button type="button" @click="navOpen = !navOpen" class="rounded-md p-2 text-zinc-600 hover:bg-zinc-100 sm:hidden dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="Menu">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <nav class="hidden items-center gap-1 text-sm sm:flex">
            <a href="{{ route('client-portal.dashboard') }}" wire:navigate @class([
                'rounded-md px-2.5 py-1.5 font-medium transition',
                'bg-accent-50 text-accent-800 dark:bg-accent-600/20 dark:text-accent-300' => request()->routeIs('client-portal.dashboard'),
                'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800' => ! request()->routeIs('client-portal.dashboard'),
            ])>Dashboard</a>
            <a href="{{ route('client-portal.invoices') }}" wire:navigate @class([
                'rounded-md px-2.5 py-1.5 font-medium transition',
                'bg-accent-50 text-accent-800 dark:bg-accent-600/20 dark:text-accent-300' => request()->routeIs('client-portal.invoices*'),
                'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800' => ! request()->routeIs('client-portal.invoices*'),
            ])>Invoices</a>
            <a href="{{ route('client-portal.approvals') }}" wire:navigate @class([
                'rounded-md px-2.5 py-1.5 font-medium transition',
                'bg-accent-50 text-accent-800 dark:bg-accent-600/20 dark:text-accent-300' => request()->routeIs('client-portal.approvals'),
                'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800' => ! request()->routeIs('client-portal.approvals'),
            ])>Approvals</a>
            <form method="POST" action="{{ route('logout') }}" class="ml-1">
                @csrf
                <button type="submit" class="btn-secondary !px-2.5 !py-1.5 text-xs text-red-700 dark:text-red-400">Sign out</button>
            </form>
        </nav>
    </div>
    <nav x-show="navOpen" x-cloak class="border-t border-zinc-100 px-4 py-2 sm:hidden dark:border-zinc-800">
        <a href="{{ route('client-portal.dashboard') }}" wire:navigate class="block rounded-md px-2 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">Dashboard</a>
        <a href="{{ route('client-portal.invoices') }}" wire:navigate class="block rounded-md px-2 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">Invoices</a>
        <a href="{{ route('client-portal.approvals') }}" wire:navigate class="block rounded-md px-2 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">Approvals</a>
        <form method="POST" action="{{ route('logout') }}" class="mt-1">
            @csrf
            <button type="submit" class="w-full rounded-md px-2 py-2 text-left text-sm text-red-700 dark:text-red-400">Sign out</button>
        </form>
    </nav>
</header>
<main class="mx-auto max-w-6xl px-4 py-5 sm:px-6 sm:py-6">{{ $slot }}</main>
@livewireScripts
</body>
</html>
