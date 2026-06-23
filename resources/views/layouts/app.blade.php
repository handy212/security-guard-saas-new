<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'GuardOps SaaS') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="bg-slate-50" x-data="{ sidebarOpen: false }">
<div class="min-h-screen lg:flex" wire:loading.class="opacity-95">
    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden"></div>

    {{-- Sidebar --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:static lg:translate-x-0">
        <div class="border-b border-slate-100 p-5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 text-sm font-black text-white">G</div>
                <div>
                    <div class="text-base font-bold text-slate-900">GuardOps</div>
                    <div class="text-[11px] text-slate-500">Security Operations</div>
                </div>
            </div>
            @auth
                <div class="mt-4 flex items-center gap-3 rounded-lg bg-slate-50 p-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-800">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium">{{ auth()->user()->name }}</div>
                        <div class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</div>
                    </div>
                </div>
            @endauth
        </div>

        <nav class="flex-1 overflow-y-auto p-4 space-y-5 text-sm">
            @foreach(config('navigation.navigation') as $group => $links)
                @php
                    $visible = collect($links)->filter(fn ($link) => empty($link['permission']) || auth()->user()?->can($link['permission']));
                @endphp
                @if($visible->isNotEmpty())
                    <div>
                        <div class="mb-2 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $group }}</div>
                        <div class="space-y-0.5">
                            @foreach($visible as $link)
                                @php $active = request()->is(ltrim($link['href'], '/').'*'); @endphp
                                <a href="{{ $link['href'] }}"
                                   @click="sidebarOpen = false"
                                   class="block rounded-lg px-3 py-2 font-medium transition {{ $active ? 'nav-link-active' : 'nav-link-idle' }}">
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>

        @auth
            <div class="border-t border-slate-100 p-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-secondary w-full justify-center text-red-700 hover:bg-red-50 hover:text-red-800">
                        Sign out
                    </button>
                </form>
            </div>
        @endauth
    </aside>

    {{-- Main --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-slate-200 bg-white/90 px-4 py-3 backdrop-blur lg:hidden">
            <button type="button" @click="sidebarOpen = true" class="btn-secondary !p-2" aria-label="Open menu">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="font-bold text-slate-900">GuardOps</div>
        </header>

        <div wire:loading class="fixed inset-x-0 top-0 z-50 h-0.5 bg-brand-500">
            <div class="h-full w-1/3 animate-pulse bg-brand-300"></div>
        </div>

        @if (session('status'))
            <x-flash-status type="success" class="border-b" />
        @endif

        <main class="flex-1">{{ $slot }}</main>
    </div>
</div>
@livewireScripts
@stack('scripts')
</body>
</html>
