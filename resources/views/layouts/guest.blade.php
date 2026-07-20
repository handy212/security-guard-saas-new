<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'GuardCore Pro') }}</title>
    @include('partials.brand-assets')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-zinc-100 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <div class="pointer-events-none fixed inset-0 opacity-70" style="background:
        radial-gradient(ellipse 70% 50% at 10% 0%, color-mix(in srgb, var(--tenant-brand, #0f766e) 12%, transparent), transparent 55%),
        radial-gradient(ellipse 50% 40% at 100% 100%, color-mix(in srgb, var(--tenant-brand, #0f766e) 8%, transparent), transparent 50%);"></div>
    <div class="relative">{{ $slot }}</div>
    @livewireScripts
</body>
</html>
