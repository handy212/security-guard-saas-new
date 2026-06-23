<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#0f172a">
    <title>Guard App — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-900 text-white">
<header class="sticky top-0 z-10 border-b border-slate-700 bg-slate-900/95 px-4 py-3 backdrop-blur">
    <div class="flex items-center justify-between">
        <div class="font-bold">GuardOps Field</div>
        <a href="{{ route('dashboard') }}" class="text-xs text-slate-300">Admin</a>
    </div>
</header>
<main class="mx-auto max-w-lg p-4 pb-24">{{ $slot }}</main>
@livewireScripts
<script>
if (navigator.geolocation) {
    navigator.geolocation.watchPosition((pos) => {
        window.guardCoords = { lat: pos.coords.latitude, lng: pos.coords.longitude };
    }, () => {}, { enableHighAccuracy: true, maximumAge: 15000 });
}
document.addEventListener('livewire:init', () => {
    Livewire.hook('commit', ({ component, succeed }) => {
        succeed(() => {
            component.el.querySelectorAll('[data-geo]').forEach((el) => {
                el.addEventListener('click', () => {
                    if (window.guardCoords) {
                        component.set(el.dataset.latField || 'lat', window.guardCoords.lat);
                        component.set(el.dataset.lngField || 'lng', window.guardCoords.lng);
                    }
                }, { once: true });
            });
        });
    });
});
</script>
</body>
</html>
