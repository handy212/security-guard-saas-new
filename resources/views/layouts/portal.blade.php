<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Portal — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-100 text-slate-900">
<header class="border-b bg-white">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
        <div>
            <div class="text-lg font-bold">Client Portal</div>
            <div class="text-xs text-slate-500">Proof of service and reports</div>
        </div>
        <nav class="flex gap-4 text-sm">
            <a href="{{ route('client-portal.dashboard') }}" class="hover:underline">Dashboard</a>
            <a href="{{ route('client-portal.approvals') }}" class="hover:underline">Approvals</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-red-600 hover:underline">Sign out</button></form>
        </nav>
    </div>
</header>
<main class="mx-auto max-w-6xl p-4 md:p-6">{{ $slot }}</main>
@livewireScripts
</body>
</html>
