@php
    $nav = app(\App\Services\NavigationBuilder::class);
    $searchableLinks = $nav->searchableLinks();
@endphp

<header class="sticky top-0 z-30 border-b border-zinc-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80">
    <div class="page-content flex items-center gap-3 py-2.5">
        <button
            type="button"
            @click="sidebarOpen = true"
            class="btn-secondary !p-2 lg:hidden"
            aria-label="Open navigation"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
            <div
                class="relative flex items-center"
                x-data="{ searchOpen: false }"
                @keydown.escape.window="searchOpen = false"
            >
                <button
                    type="button"
                    x-show="!searchOpen"
                    @click="searchOpen = true; $nextTick(() => $refs.headerSearch?.focus())"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900"
                    aria-label="Search"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
                <div
                    x-show="searchOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute right-0 top-0 z-50 flex w-[min(20rem,calc(100vw-6rem))] items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-1.5 shadow-lg sm:w-72"
                    @click.outside="searchOpen = false"
                >
                    <svg class="h-4 w-4 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input
                        x-ref="headerSearch"
                        type="text"
                        placeholder="Search pages…"
                        class="min-w-0 flex-1 border-0 bg-transparent text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-0"
                        @focus="commandOpen = true; searchOpen = false"
                        @keydown.enter.prevent="commandOpen = true; searchOpen = false"
                    >
                    <kbd class="hidden rounded border border-zinc-200 px-1.5 py-0.5 text-[10px] text-zinc-400 sm:inline">⌘K</kbd>
                </div>
            </div>

            @if (! \App\Support\TenantContext::isPlatformConsole())
                <livewire:notifications.notification-bell />
            @endif

            <div class="relative" x-data="{ userOpen: false }">
                <button
                    type="button"
                    @click="userOpen = !userOpen"
                    class="flex h-9 items-center gap-2 rounded-lg py-1 pl-1 pr-2 text-sm transition hover:bg-zinc-100"
                    aria-label="User menu"
                >
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-accent-100 text-xs font-semibold text-accent-700">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <span class="hidden max-w-[8rem] truncate font-medium text-zinc-800 md:inline">{{ auth()->user()->name }}</span>
                    <svg class="hidden h-3.5 w-3.5 text-zinc-400 md:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div
                    x-show="userOpen"
                    @click.outside="userOpen = false"
                    x-transition
                    x-cloak
                    class="absolute right-0 z-50 mt-1 w-48 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg"
                >
                    <div class="border-b border-zinc-100 px-3 py-2">
                        <p class="truncate text-sm font-medium text-zinc-900">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</p>
                    </div>
                    @can('settings.manage')
                        <a href="{{ route('settings.index') }}" class="block px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">Settings</a>
                    @endcan
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full px-3 py-2 text-left text-sm text-red-700 hover:bg-zinc-50">Sign out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<div
    x-cloak
    x-show="commandOpen"
    x-transition.opacity
    class="fixed inset-0 z-[80] flex items-start justify-center bg-zinc-900/50 px-4 pt-[12vh]"
    @click.self="commandOpen = false"
    @keydown.escape.window="commandOpen = false"
>
    <div
        class="w-full max-w-lg overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xl"
        @click.stop
        x-data="commandPalette(@js($searchableLinks))"
        x-effect="if ($root.commandOpen) { $nextTick(() => $refs.query?.focus()) }"
    >
        <div class="flex items-center gap-2 border-b border-zinc-100 px-4 py-3">
            <svg class="h-5 w-5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input
                x-ref="query"
                x-model="query"
                type="text"
                placeholder="Search pages…"
                class="flex-1 border-0 bg-transparent text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-0"
                @keydown.arrow-down.prevent="move(1)"
                @keydown.arrow-up.prevent="move(-1)"
                @keydown.enter.prevent="go()"
            >
            <kbd class="rounded border border-zinc-200 px-1.5 py-0.5 text-[10px] text-zinc-400">esc</kbd>
        </div>
        <ul class="max-h-72 overflow-y-auto py-2">
            <template x-for="(item, index) in filtered" :key="item.href">
                <li>
                    <a
                        :href="item.href"
                        class="flex items-center justify-between px-4 py-2 text-sm transition"
                        :class="index === activeIndex ? 'bg-accent-50 text-accent-700' : 'text-zinc-700 hover:bg-zinc-50'"
                        @mouseenter="activeIndex = index"
                        @click="commandOpen = false"
                    >
                        <span x-text="item.label"></span>
                        <span class="text-xs text-zinc-400" x-text="item.group"></span>
                    </a>
                </li>
            </template>
            <li x-show="filtered.length === 0" class="px-4 py-6 text-center text-sm text-zinc-500">No matching pages</li>
        </ul>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('commandPalette', (links) => ({
                    query: '',
                    activeIndex: 0,
                    links,
                    get filtered() {
                        const q = this.query.trim().toLowerCase();
                        if (!q) return this.links.slice(0, 12);
                        return this.links.filter((item) =>
                            item.label.toLowerCase().includes(q)
                            || (item.group && item.group.toLowerCase().includes(q))
                            || item.href.toLowerCase().includes(q)
                        ).slice(0, 12);
                    },
                    move(delta) {
                        const max = this.filtered.length - 1;
                        if (max < 0) return;
                        this.activeIndex = Math.max(0, Math.min(max, this.activeIndex + delta));
                    },
                    go() {
                        const item = this.filtered[this.activeIndex];
                        if (item) {
                            this.$root.commandOpen = false;
                            window.location.href = item.href;
                        }
                    },
                }));
            });
        </script>
    @endpush
@endonce
