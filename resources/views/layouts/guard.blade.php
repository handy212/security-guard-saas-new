<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(config('notifications.push.vapid.public_key'))
        <meta name="vapid-public-key" content="{{ config('notifications.push.vapid.public_key') }}">
    @endif
    <meta name="theme-color" content="#09090b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <title>Field — {{ $platformBranding->name() }}@isset($tenantBranding['name']) — {{ $tenantBranding['name'] }}@endisset</title>
    @include('partials.brand-assets')
    @vite(['resources/css/app.css', 'resources/js/guard-pwa.js'])
    @livewireStyles
</head>
<body data-theme-mode="dark"
    class="field-app bg-zinc-950 font-sans text-zinc-100 antialiased"
    x-data="{
        tabs: ['assignments', 'dispatches', 'patrol', 'scan', 'tools', 'sos', 'notifications', 'shift'],
        tab: 'assignments',
        readTab() {
            const requested = window.location.hash.slice(1);
            this.showTab(this.tabs.includes(requested) ? requested : 'assignments', false);
        },
        openTool(id) {
            this.showTab('tools');
            this.$nextTick(() => {
                const panel = document.getElementById(id);
                if (!panel) return;
                panel.open = true;
                panel.querySelector('summary')?.focus({ preventScroll: true });
                panel.scrollIntoView({ block: 'start' });
            });
        },
        activeNav(key) {
            return this.tab === key || (key === 'assignments' && this.tab === 'shift');
        },
        showTab(next, updateUrl = true) {
            if (this.tab === next) return;
            this.tab = next;
            if (updateUrl) history.pushState(null, '', '#' + next);
            window.scrollTo(0, 0);
            this.$dispatch('field-tab-changed', { tab: next });
        }
    }"
    x-init="readTab()"
    x-on:hashchange.window="readTab()"
    x-on:popstate.window="readTab()"
>
@include('partials.brand-colors')
<div class="shell">
    <header class="app-header">
        <div class="top">
            <div class="brand">
                <div class="mark" aria-hidden="true">◆</div>
                <div class="min-w-0">
                    <strong>GuardCore <span>Pro</span></strong>
                    <div class="sub">{{ $tenantBranding['name'] ?? 'Field operations' }}</div>
                </div>
            </div>
            <div class="buttons">
                <a href="#notifications" x-on:click.prevent="showTab('notifications')" class="iconbtn grid place-items-center" aria-label="Notifications">
                    <span aria-hidden="true">♧</span>
                </a>
                <a href="#sos" x-on:click.prevent="showTab('sos')" class="sos">◉ SOS</a>
            </div>
        </div>
        <div class="statusline">
            <span class="status" id="guard-gps-status">GPS not checked</span>
            <span class="status" id="guard-connection-status">Online</span>
            @can('dashboard.view')
                <a href="{{ route('dashboard') }}" class="status">Admin</a>
            @endcan
        </div>
    </header>

    <main class="content">{{ $slot }}</main>

    <nav class="bottom" aria-label="Field navigation">
        @foreach ([
            'assignments' => ['⌂', 'Home'], 'dispatches' => ['♧', 'Dispatch'], 'patrol' => ['◇', 'Patrol'],
            'scan' => ['▦', 'Scan'], 'tools' => ['☷', 'More'],
        ] as $key => [$icon, $label])
            <a href="#{{ $key }}" x-on:click.prevent="showTab(@js($key))"
                x-bind:aria-current="activeNav(@js($key)) ? 'page' : null" class="nav" x-bind:class="{ active: activeNav(@js($key)) }">
                <span class="glyph" aria-hidden="true">{{ $icon }}</span>{{ $label }}
            </a>
        @endforeach
    </nav>
</div>

@livewireScripts
<script>
    const statusEl = document.getElementById('guard-connection-status');
    const updateStatus = () => {
        if (!statusEl) return;
        statusEl.textContent = navigator.onLine ? 'Online' : 'Offline';
        statusEl.classList.toggle('green', navigator.onLine);
    };
    window.addEventListener('online', updateStatus);
    window.addEventListener('offline', updateStatus);
    updateStatus();

</script>
</body>
</html>
