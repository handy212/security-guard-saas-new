<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
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
        <a href="{{ route('dashboard') }}" class="text-xs text-slate-300">Admin</a>
    </div>
</header>
<main class="mx-auto max-w-lg p-4 pb-24" data-guard-app>{{ $slot }}</main>
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
