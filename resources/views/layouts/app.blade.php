<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'GuardOps SaaS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-900">
<div class="min-h-screen md:flex">
    <aside class="w-full border-r bg-white p-4 md:sticky md:top-0 md:h-screen md:w-72 md:overflow-y-auto">
        <div class="mb-6">
            <div class="text-xl font-black">GuardOps SaaS</div>
            <div class="text-xs text-slate-500">Enterprise Security Platform</div>
            @auth
                <div class="mt-2 text-xs text-slate-600">{{ auth()->user()->name }}</div>
                <form method="POST" action="{{ route('logout') }}" class="mt-1">
                    @csrf
                    <button type="submit" class="text-xs text-red-600 hover:underline">Sign out</button>
                </form>
            @endauth
        </div>
        <nav class="space-y-4 text-sm">
            @foreach(config('navigation.navigation') as $group => $links)
                @php
                    $visible = collect($links)->filter(fn ($link) => empty($link['permission']) || auth()->user()?->can($link['permission']));
                @endphp
                @if($visible->isNotEmpty())
                    <div>
                        <div class="mb-1 px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $group }}</div>
                        <div class="space-y-1">
                            @foreach($visible as $link)
                                <a class="block rounded-lg px-3 py-2 hover:bg-slate-100 {{ request()->is(ltrim($link['href'], '/').'*') ? 'bg-slate-900 text-white' : '' }}"
                                   href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>
    </aside>
    <main class="flex-1 min-w-0">
        @if (session('status'))
            <x-flash-status class="border-b" />
        @endif
        {{ $slot }}
    </main>
</div>
@livewireScripts
@stack('scripts')
</body>
</html>
