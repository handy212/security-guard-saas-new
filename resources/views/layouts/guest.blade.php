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
<body class="min-h-screen bg-zinc-50 font-sans antialiased text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    {{ $slot }}
    @livewireScripts
</body>
</html>
