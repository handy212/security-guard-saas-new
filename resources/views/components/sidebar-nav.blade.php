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
    x-data="sidebarNav(@js($activeGroup))"
>
    <div class="flex-1 overflow-y-auto overscroll-contain px-1.5 py-3 lg:px-2">
        @if ($platform->isNotEmpty())
            <div class="space-y-0.5">
                <p class="mb-1 px-2.5 text-[11px] font-semibold uppercase tracking-wide text-zinc-400" x-show="!sidebarCollapsed" x-cloak>Platform</p>
                @foreach ($platform as $link)
                    @php $active = $nav->isLinkActive($link); @endphp
                    <a href="{{ $link['href'] }}"
                       @click="sidebarOpen = false"
                       title="{{ $link['label'] }}"
                       class="nav-link {{ $active ? 'nav-link-active' : '' }}"
                       :class="sidebarCollapsed ? 'nav-rail-item' : ''">
                        <span class="nav-rail-icon"><x-nav-icon :name="$link['icon'] ?? 'dashboard'" class="h-[18px] w-[18px] shrink-0" /></span>
                        <span x-show="!sidebarCollapsed" x-cloak class="truncate">{{ $link['label'] }}</span>
                        <span x-show="sidebarCollapsed" x-cloak class="nav-rail-label">{{ $nav->shortLabel($link['label']) }}</span>
                    </a>
                @endforeach
            </div>
        @else
            {{-- Expanded: full pinned list --}}
            @if ($pinned->isNotEmpty())
                <div class="space-y-0.5" x-show="!sidebarCollapsed" x-cloak>
                    @foreach ($pinned as $link)
                        @php $active = $nav->isLinkActive($link); @endphp
                        <a href="{{ $link['href'] }}"
                           @click="sidebarOpen = false"
                           title="{{ $link['label'] }}"
                           class="nav-link nav-link-fav group {{ $active ? 'nav-link-active' : '' }} {{ ! empty($link['highlight']) ? 'nav-link-highlight' : '' }}">
                            <x-nav-icon :name="$link['icon'] ?? 'dashboard'" class="h-[18px] w-[18px] shrink-0" />
                            <span class="min-w-0 flex-1 truncate">{{ $link['label'] }}</span>
                            <button
                                type="button"
                                @click.prevent="toggleFavorite(@js($link['href']), @js($link['label']))"
                                class="nav-fav-star"
                                :class="isFavorite(@js($link['href'])) ? 'nav-fav-star-on' : ''"
                                aria-label="Favorite"
                            >
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            </button>
                        </a>
                    @endforeach
                </div>

                {{-- Collapsed: icon rail with flyouts for hubs --}}
                <div class="space-y-1" x-show="sidebarCollapsed" x-cloak>
                    @foreach ($pinned as $link)
                        @php
                            $active = $nav->isLinkActive($link);
                            $children = $nav->flyoutChildren($link);
                            $flyoutId = 'pinned-'.$loop->index;
                        @endphp
                        <div
                            class="relative"
                            @mouseenter="showFlyout(@js($flyoutId), $el)"
                            @mouseleave="hideFlyout()"
                            @focusin="showFlyout(@js($flyoutId), $el)"
                            @focusout="hideFlyout()"
                        >
                            <a href="{{ $link['href'] }}"
                               @click="sidebarOpen = false"
                               class="nav-link nav-rail-item {{ $active ? 'nav-link-active' : '' }} {{ ! empty($link['highlight']) ? 'nav-link-highlight' : '' }}"
                               :class="flyout === @js($flyoutId) ? 'nav-rail-item-hot' : ''"
                               aria-haspopup="{{ count($children) ? 'true' : 'false' }}"
                               :aria-expanded="flyout === @js($flyoutId) ? 'true' : 'false'">
                                <span class="nav-rail-icon"><x-nav-icon :name="$link['icon'] ?? 'dashboard'" class="h-5 w-5 shrink-0" /></span>
                                <span class="nav-rail-label">{{ $nav->shortLabel($link['label']) }}</span>
                            </a>

                            @if (count($children))
                                <div
                                    x-show="flyout === @js($flyoutId)"
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 translate-x-1"
                                    x-transition:enter-end="opacity-100 translate-x-0"
                                    class="nav-flyout"
                                    data-flyout-id="{{ $flyoutId }}"
                                    @mouseenter="showFlyout(@js($flyoutId))"
                                    @mouseleave="hideFlyout()"
                                >
                                    <div class="nav-flyout-panel">
                                        <p class="nav-flyout-title">{{ $link['label'] }}</p>
                                        <div class="nav-flyout-list">
                                            @foreach ($children as $child)
                                                @php $childActive = $nav->isLinkActive($child); @endphp
                                                <a href="{{ $child['href'] }}"
                                                   @click="sidebarOpen = false; flyout = null"
                                                   class="nav-flyout-link {{ $childActive ? 'nav-flyout-link-active' : '' }}">
                                                    {{ $child['label'] }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <template x-if="favorites.length > 0 && !sidebarCollapsed">
                <div class="nav-favorites mt-3 space-y-0.5">
                    <p class="nav-section-label">Favorites</p>
                    <template x-for="item in favorites" :key="item.href">
                        <div class="nav-favorite-row group">
                            <a :href="item.href"
                               @click="sidebarOpen = false"
                               class="nav-link nav-favorite-link"
                               :class="isActivePath(item.href) ? 'nav-link-active' : ''">
                                <svg class="h-3.5 w-3.5 shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <span class="min-w-0 flex-1 truncate" x-text="item.label"></span>
                            </a>
                            <button
                                type="button"
                                class="nav-favorite-remove"
                                @click.prevent="removeFavorite(item.href)"
                                aria-label="Remove favorite"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </template>

            @if ($pinned->isNotEmpty() && $groups->isNotEmpty())
                <div class="my-3 border-t border-zinc-800" x-show="!sidebarCollapsed" x-cloak></div>
                <div class="my-2 border-t border-zinc-800" x-show="sidebarCollapsed" x-cloak></div>
                <p class="mb-1 px-1 text-center text-[9px] font-semibold uppercase tracking-wider text-zinc-500" x-show="sidebarCollapsed" x-cloak>More</p>
            @endif

            <div class="space-y-1">
                @foreach ($groups as $group)
                    @php
                        $groupActive = collect($group['links'])->contains(fn ($link) => $nav->isLinkActive($link));
                        $flyoutId = 'group-'.$loop->index;
                    @endphp

                    {{-- Expanded: accordion group --}}
                    <div class="nav-group" x-show="!sidebarCollapsed" x-cloak>
                        <button
                            type="button"
                            class="nav-group-toggle"
                            :class="open === @js($group['label']) || @js($groupActive) ? 'nav-group-toggle-active' : ''"
                            @click="toggleGroup(@js($group['label']))"
                            :aria-expanded="open === @js($group['label']) ? 'true' : 'false'"
                        >
                            <x-nav-icon :name="$group['icon']" class="h-4 w-4 shrink-0 opacity-70" />
                            <span class="min-w-0 flex-1 truncate text-left">{{ $group['label'] }}</span>
                            <svg class="h-3.5 w-3.5 shrink-0 text-zinc-500 transition-transform duration-150" :class="open === @js($group['label']) ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        <div
                            class="nav-group-panel"
                            x-show="open === @js($group['label'])"
                            x-cloak
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 -translate-y-0.5"
                            x-transition:enter-end="opacity-100 translate-y-0"
                        >
                            @foreach ($group['links'] as $link)
                                @php $active = $nav->isLinkActive($link); @endphp
                                <a href="{{ $link['href'] }}"
                                   @click="sidebarOpen = false"
                                   class="nav-sublink {{ $active ? 'nav-sublink-active' : '' }}">
                                    <x-nav-icon :name="$link['icon'] ?? $group['icon']" class="h-3.5 w-3.5 shrink-0 opacity-60" />
                                    <span class="truncate">{{ $link['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Collapsed: icon + flyout --}}
                    <div
                        class="relative"
                        x-show="sidebarCollapsed"
                        x-cloak
                        @mouseenter="showFlyout(@js($flyoutId), $el)"
                        @mouseleave="hideFlyout()"
                        @focusin="showFlyout(@js($flyoutId), $el)"
                        @focusout="hideFlyout()"
                    >
                        <a href="{{ $group['href'] }}"
                           @click="sidebarOpen = false"
                           class="nav-link nav-rail-item {{ $groupActive ? 'nav-link-active' : '' }}"
                           :class="flyout === @js($flyoutId) ? 'nav-rail-item-hot' : ''"
                           aria-haspopup="true"
                           :aria-expanded="flyout === @js($flyoutId) ? 'true' : 'false'">
                            <span class="nav-rail-icon"><x-nav-icon :name="$group['icon']" class="h-5 w-5 shrink-0" /></span>
                            <span class="nav-rail-label">{{ $nav->shortLabel($group['label']) }}</span>
                        </a>

                        <div
                            x-show="flyout === @js($flyoutId)"
                            x-cloak
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 translate-x-1"
                            x-transition:enter-end="opacity-100 translate-x-0"
                            class="nav-flyout"
                            data-flyout-id="{{ $flyoutId }}"
                            @mouseenter="showFlyout(@js($flyoutId))"
                            @mouseleave="hideFlyout()"
                        >
                            <div class="nav-flyout-panel">
                                <p class="nav-flyout-title">{{ $group['label'] }}</p>
                                <div class="nav-flyout-list">
                                    @foreach ($group['links'] as $link)
                                        @php $active = $nav->isLinkActive($link); @endphp
                                        <a href="{{ $link['href'] }}"
                                           @click="sidebarOpen = false; flyout = null"
                                           class="nav-flyout-link {{ $active ? 'nav-flyout-link-active' : '' }}">
                                            <x-nav-icon :name="$link['icon'] ?? $group['icon']" class="h-4 w-4 shrink-0 opacity-70" />
                                            <span>{{ $link['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if ($footer->isNotEmpty())
        <div class="shrink-0 border-t border-zinc-800 p-1.5 lg:p-2">
            @foreach ($footer as $link)
                @php
                    $active = $nav->isLinkActive($link);
                    $children = $nav->flyoutChildren($link);
                    $flyoutId = 'footer-'.$loop->index;
                @endphp
                <div
                    class="relative"
                    @mouseenter="sidebarCollapsed && showFlyout(@js($flyoutId), $el)"
                    @mouseleave="sidebarCollapsed && hideFlyout()"
                >
                    <a href="{{ $link['href'] }}"
                       @click="sidebarOpen = false"
                       title="{{ $link['label'] }}"
                       class="nav-link {{ $active ? 'nav-link-active' : '' }}"
                       :class="sidebarCollapsed ? 'nav-rail-item' : ''"
                       :aria-expanded="flyout === @js($flyoutId) ? 'true' : 'false'">
                        <span class="nav-rail-icon"><x-nav-icon :name="$link['icon'] ?? 'settings'" class="h-[18px] w-[18px] shrink-0" /></span>
                        <span x-show="!sidebarCollapsed" x-cloak class="truncate">{{ $link['label'] }}</span>
                        <span x-show="sidebarCollapsed" x-cloak class="nav-rail-label">{{ $nav->shortLabel($link['label']) }}</span>
                    </a>

                    @if (count($children))
                        <div
                            x-show="sidebarCollapsed && flyout === @js($flyoutId)"
                            x-cloak
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 translate-x-1"
                            x-transition:enter-end="opacity-100 translate-x-0"
                            class="nav-flyout"
                            data-flyout-id="{{ $flyoutId }}"
                            @mouseenter="showFlyout(@js($flyoutId))"
                            @mouseleave="hideFlyout()"
                        >
                            <div class="nav-flyout-panel">
                                <p class="nav-flyout-title">{{ $link['label'] }}</p>
                                <div class="nav-flyout-list">
                                    @foreach ($children as $child)
                                        @php $childActive = $nav->isLinkActive($child); @endphp
                                        <a href="{{ $child['href'] }}"
                                           @click="sidebarOpen = false; flyout = null"
                                           class="nav-flyout-link {{ $childActive ? 'nav-flyout-link-active' : '' }}">
                                            {{ $child['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</nav>
