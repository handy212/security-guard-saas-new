<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'GuardOps SaaS') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="bg-zinc-100 antialiased text-zinc-900" x-data="{ sidebarOpen: false }">
<div wire:loading.class="opacity-95">
    <div
        x-show="sidebarOpen"
        x-cloak
        @click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-zinc-900/60 lg:hidden"
    ></div>

    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-50 flex w-60 flex-col border-r border-zinc-200 bg-white transition-transform duration-200 ease-out lg:translate-x-0"
    >
        <div class="flex h-14 shrink-0 items-center gap-2.5 border-b border-zinc-100 px-4">
            <a href="{{ \App\Support\TenantContext::isPlatformAdmin() && ! \App\Support\TenantContext::isViewingAsTenant() ? route('saas.tenants') : route('dashboard') }}" class="flex min-w-0 flex-1 items-center gap-2.5">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-900 text-xs font-bold text-white">G</div>
                <div class="min-w-0 leading-tight">
                    <div class="truncate text-sm font-semibold text-zinc-900">GuardOps</div>
                    <div class="truncate text-[11px] text-zinc-500">Security Operations</div>
                </div>
            </a>
            <button
                type="button"
                @click="sidebarOpen = false"
                class="rounded-md p-2 text-zinc-500 hover:bg-zinc-100 lg:hidden"
                aria-label="Close navigation"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <x-sidebar-nav />

        @auth
            <div class="shrink-0 border-t border-zinc-100 p-3">
                <div class="mb-2 truncate px-1 text-xs font-medium text-zinc-700">{{ auth()->user()->name }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-secondary w-full justify-center text-xs">Sign out</button>
                </form>
            </div>
        @endauth
    </aside>

    <div class="min-h-screen lg:pl-60">
        <div wire:loading class="fixed inset-x-0 top-0 z-[70] h-0.5 bg-zinc-900 lg:left-60">
            <div class="h-full w-1/3 animate-pulse bg-zinc-400"></div>
        </div>

        @if (session('status'))
            <x-flash-status type="success" class="border-b lg:fixed lg:inset-x-0 lg:top-0 lg:z-[70] lg:left-60" />
        @endif

        @if (\App\Support\TenantContext::isViewingAsTenant())
            <div class="flex items-center justify-center gap-2 border-b border-amber-200 bg-amber-50 px-4 py-2.5 text-center text-sm text-amber-900">
                <span>Viewing <strong>{{ \App\Support\TenantContext::current()?->name }}</strong> as platform admin.</span>
                <form method="POST" action="{{ route('saas.exit-tenant') }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-link text-amber-900">Exit to platform</button>
                </form>
            </div>
        @else
            <x-plan-usage-banner />
        @endif

        @auth
            @if (! \App\Support\TenantContext::isPlatformConsole())
                <div class="flex items-center justify-end gap-2 border-b border-zinc-100 bg-white px-4 py-2 lg:sticky lg:top-0 lg:z-30 lg:bg-white/95 lg:py-2 lg:backdrop-blur supports-[backdrop-filter]:lg:bg-white/80 lg:pl-[calc(15rem+1rem)]">
                    <livewire:notifications.notification-bell />
                </div>
            @endif
        @endauth

        <main>
            {{ $slot }}
        </main>
    </div>
</div>
@livewireScripts
@stack('scripts')
</body>
</html>
