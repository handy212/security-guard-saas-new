@php
    $nav = app(\App\Services\NavigationBuilder::class);
    $pinned = $nav->pinned();
    $groups = $nav->groups();
    $footer = $nav->footer();
    $platform = $nav->platform();
    $activeGroup = $nav->activeGroupLabel();
@endphp

<nav
    class="flex min-h-0 flex-1 flex-col overflow-hidden"
    x-data="{
        open: @js($activeGroup),
        favorites: JSON.parse(localStorage.getItem('guardops-nav-favorites') || '[]'),
        toggleFavorite(href, label) {
            const i = this.favorites.findIndex((f) => f.href === href);
            if (i >= 0) { this.favorites.splice(i, 1); } else { this.favorites.push({ href, label }); }
            localStorage.setItem('guardops-nav-favorites', JSON.stringify(this.favorites));
        },
        isFavorite(href) { return this.favorites.some((f) => f.href === href); }
    }"
>
    <div class="flex-1 overflow-y-auto overscroll-contain px-2 py-3">
        @if ($platform->isNotEmpty())
            <div class="space-y-0.5">
                <p class="mb-1 px-2.5 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">Platform</p>
                @foreach ($platform as $link)
                    @php $active = $nav->isLinkActive($link); @endphp
                    <a href="{{ $link['href'] }}"
                       @click="sidebarOpen = false"
                       title="{{ $link['label'] }}"
                       class="nav-link {{ $active ? 'nav-link-active' : '' }}"
                       :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                        <x-nav-icon :name="$link['icon'] ?? 'dashboard'" class="h-[18px] w-[18px] shrink-0" />
                        <span x-show="!sidebarCollapsed" x-cloak class="truncate">{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @else
            @if ($pinned->isNotEmpty())
                <div class="space-y-0.5">
                    @foreach ($pinned as $link)
                        @php $active = $nav->isLinkActive($link); @endphp
                        <a href="{{ $link['href'] }}"
                           @click="sidebarOpen = false"
                           title="{{ $link['label'] }}"
                           class="nav-link group {{ $active ? 'nav-link-active' : '' }} {{ ! empty($link['highlight']) ? 'nav-link-highlight' : '' }}"
                           :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                            <x-nav-icon :name="$link['icon'] ?? 'dashboard'" class="h-[18px] w-[18px] shrink-0" />
                            <span x-show="!sidebarCollapsed" x-cloak class="flex-1 truncate">{{ $link['label'] }}</span>
                            <button
                                type="button"
                                x-show="!sidebarCollapsed"
                                x-cloak
                                @click.prevent="toggleFavorite(@js($link['href']), @js($link['label']))"
                                class="ml-auto shrink-0 rounded p-0.5 text-zinc-300 opacity-0 transition hover:text-amber-500 group-hover:opacity-100"
                                :class="isFavorite(@js($link['href'])) ? '!text-amber-500 !opacity-100' : ''"
                                aria-label="Favorite"
                            >
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            </button>
                        </a>
                    @endforeach
                </div>
            @endif

            <template x-if="favorites.length > 0 && !sidebarCollapsed">
                <div class="mt-3 space-y-0.5">
                    <p class="mb-1 px-2.5 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">Favorites</p>
                    <template x-for="item in favorites" :key="item.href">
                        <a :href="item.href" @click="sidebarOpen = false" class="nav-link" x-text="item.label"></a>
                    </template>
                </div>
            </template>

            @if ($pinned->isNotEmpty() && $groups->isNotEmpty())
                <div class="my-3 border-t border-zinc-100"></div>
            @endif

            <div class="space-y-2">
                @foreach ($groups as $group)
                    @php
                        $groupActive = collect($group['links'])->contains(fn ($link) => $nav->isLinkActive($link));
                    @endphp
                    <div x-show="!sidebarCollapsed" x-cloak>
                        <button
                            type="button"
                            @click="open = open === @js($group['label']) ? null : @js($group['label'])"
                            class="flex w-full items-center justify-between rounded-md px-2.5 py-1.5 text-left text-[11px] font-semibold uppercase tracking-wide transition {{ $groupActive ? 'text-zinc-700' : 'text-zinc-400 hover:text-zinc-600' }}"
                        >
                            <span>{{ $group['label'] }}</span>
                            <svg class="h-3.5 w-3.5 shrink-0 transition-transform duration-200" :class="open === @js($group['label']) ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div
                            x-show="open === @js($group['label'])"
                            class="mt-0.5 space-y-0.5 border-l border-zinc-200 ml-3 pl-2"
                        >
                            @foreach ($group['links'] as $link)
                                @php $active = $nav->isLinkActive($link); @endphp
                                <a href="{{ $link['href'] }}"
                                   @click="sidebarOpen = false"
                                   class="nav-sublink {{ $active ? 'nav-sublink-active' : '' }}">
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Collapsed sidebar: show group icon stack --}}
                    <div x-show="sidebarCollapsed" x-cloak class="space-y-0.5">
                        @foreach ($group['links'] as $link)
                            @php $active = $nav->isLinkActive($link); @endphp
                            <a href="{{ $link['href'] }}"
                               @click="sidebarOpen = false"
                               title="{{ $group['label'] }} · {{ $link['label'] }}"
                               class="nav-link justify-center px-2 {{ $active ? 'nav-link-active' : '' }}">
                                <x-nav-icon :name="$link['icon'] ?? 'dashboard'" class="h-[18px] w-[18px] shrink-0" />
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if ($footer->isNotEmpty())
        <div class="shrink-0 border-t border-zinc-100 p-2">
            @foreach ($footer as $link)
                @php $active = $nav->isLinkActive($link); @endphp
                <a href="{{ $link['href'] }}"
                   @click="sidebarOpen = false"
                   title="{{ $link['label'] }}"
                   class="nav-link {{ $active ? 'nav-link-active' : '' }}"
                   :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                    <x-nav-icon :name="$link['icon'] ?? 'settings'" class="h-[18px] w-[18px] shrink-0" />
                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">{{ $link['label'] }}</span>
                </a>
            @endforeach
        </div>
    @endif
</nav>
