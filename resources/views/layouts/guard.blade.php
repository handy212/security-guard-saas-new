<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <title>Guard App — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/guard-pwa.js'])
    @livewireStyles
</head>
<body class="bg-slate-900 text-white">
<header class="sticky top-0 z-20 border-b border-slate-700 bg-slate-900/95 px-4 py-3 backdrop-blur">
    <div class="flex items-center justify-between">
        <div>
            <div class="font-bold">GuardOps Field</div>
            <div class="text-[10px] text-slate-400" id="guard-connection-status">Online</div>
        </div>
        <a href="{{ route('dashboard') }}" class="rounded-md px-2 py-1 text-xs text-slate-300 hover:bg-slate-800">Admin</a>
    </div>
</header>
<main class="mx-auto max-w-lg p-4 pb-28" data-guard-app>{{ $slot }}</main>

<nav class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-700 bg-slate-900/95 backdrop-blur" aria-label="Field navigation">
    <div class="mx-auto grid max-w-lg grid-cols-3 text-center text-[11px]">
        <a href="#assignments" class="flex flex-col items-center gap-1 px-2 py-3 text-slate-300 hover:text-white">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Shift
        </a>
        <a href="#patrol" class="flex flex-col items-center gap-1 px-2 py-3 text-slate-300 hover:text-white">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
            Patrol
        </a>
        <a href="#scan" class="flex flex-col items-center gap-1 px-2 py-3 text-slate-300 hover:text-white">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            Scan
        </a>
    </div>
</nav>

@livewireScripts
<script>
    const statusEl = document.getElementById('guard-connection-status');
    const updateStatus = () => {
        if (!statusEl) return;
        statusEl.textContent = navigator.onLine ? 'Online' : 'Offline — actions will queue';
        statusEl.className = navigator.onLine ? 'text-[10px] text-emerald-400' : 'text-[10px] text-amber-400';
    };
    window.addEventListener('online', updateStatus);
    window.addEventListener('offline', updateStatus);
    updateStatus();

    document.addEventListener('livewire:init', () => {
        const root = document.querySelector('[data-guard-app]');
        if (root && window.Livewire) {
            const wire = Livewire.find(root.getAttribute('wire:id'));
            window.flushOfflineQueue?.(wire);
        }
    });
</script>
</body>
</html>
