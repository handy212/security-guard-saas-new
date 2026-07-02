<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(config('notifications.push.vapid.public_key'))
        <meta name="vapid-public-key" content="{{ config('notifications.push.vapid.public_key') }}">
    @endif
    <title>{{ config('app.name', 'GuardOps SaaS') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        try {
            if (localStorage.getItem('guardops-sidebar-collapsed') === 'true') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body
    class="bg-zinc-100 antialiased text-zinc-900"
    x-data="{
        sidebarOpen: false,
        sidebarCollapsed: document.documentElement.classList.contains('sidebar-collapsed'),
        commandOpen: false,
        toggleSidebarCollapse() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            document.documentElement.classList.toggle('sidebar-collapsed', this.sidebarCollapsed);
            localStorage.setItem('guardops-sidebar-collapsed', JSON.stringify(this.sidebarCollapsed));
        }
    }"
    @keydown.window.cmd.k.prevent="commandOpen = true"
    @keydown.window.ctrl.k.prevent="commandOpen = true"
>
<div>
    <div
        x-show="sidebarOpen"
        x-cloak
        @click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-zinc-900/60 lg:hidden"
    ></div>

    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="sidebar-width fixed inset-y-0 left-0 z-50 flex flex-col border-r border-zinc-200 bg-white max-lg:transition-transform max-lg:duration-200 max-lg:ease-out"
    >
        <div class="flex h-14 shrink-0 items-center gap-2 border-b border-zinc-100 px-3">
            <a href="{{ \App\Support\TenantContext::isPlatformAdmin() && ! \App\Support\TenantContext::isViewingAsTenant() ? route('saas.tenants') : route('dashboard') }}"
               class="flex min-w-0 flex-1 items-center gap-2.5"
               :class="sidebarCollapsed ? 'justify-center' : ''">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-accent-600 to-accent-500 text-xs font-bold text-white shadow-sm">G</div>
                <div class="min-w-0 leading-tight" x-show="!sidebarCollapsed" x-cloak>
                    <div class="truncate text-sm font-semibold text-zinc-900">GuardOps</div>
                    <div class="truncate text-[11px] text-zinc-500">Security Operations</div>
                </div>
            </a>
            <button
                type="button"
                @click="toggleSidebarCollapse()"
                class="hidden rounded-md p-1.5 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-600 lg:inline-flex"
                :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                aria-label="Toggle sidebar"
            >
                <svg class="h-4 w-4 transition-transform" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
            </button>
        </div>

        <x-sidebar-nav />

        @auth
            <div class="shrink-0 border-t border-zinc-100 p-2" x-show="sidebarCollapsed" x-cloak>
                <div class="flex justify-center">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-accent-100 text-xs font-semibold text-accent-700" title="{{ auth()->user()->name }}">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                </div>
            </div>
        @endauth
    </aside>

    <div class="main-offset min-h-screen">
        <div
            wire:loading
            class="loading-bar-offset fixed inset-x-0 top-0 z-[70] h-0.5 bg-accent-600"
        >
            <div class="h-full w-1/3 animate-pulse bg-accent-400"></div>
        </div>

        @if (session('status'))
            <x-flash-status type="success" class="fixed inset-x-0 top-0 z-[70]" />
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
            <x-app-header />
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
