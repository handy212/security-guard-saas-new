@props(['links' => [], 'active' => null])

@php
    $groups = [];
    foreach ($links as $link) {
        $group = $link['group'] ?? 'General';
        $groups[$group][] = $link;
    }
@endphp

@if (! empty($links))
    <div class="profile-mobile-nav lg:hidden">
        <label class="sr-only" for="sub-sidebar-select">Section</label>
        <select
            id="sub-sidebar-select"
            class="form-input text-sm"
            onchange="window.location.href = this.value"
        >
            @foreach ($links as $link)
                <option value="{{ $link['href'] }}" @selected($link['active'] ?? false)>{{ $link['label'] }}</option>
            @endforeach
        </select>
    </div>

    <nav {{ $attributes->merge(['class' => 'sub-sidebar hidden lg:block']) }} aria-label="Section navigation">
        @foreach ($groups as $groupLabel => $groupLinks)
            <div class="profile-sidebar-group">
                <p class="profile-sidebar-heading">{{ $groupLabel }}</p>
                <ul class="space-y-0.5">
                    @foreach ($groupLinks as $link)
                        @php
                            $href = $link['href'] ?? '#';
                            $isActive = isset($link['active']) ? (bool) $link['active'] : ($active ? request()->is(ltrim($href, '/').'*') : false);
                            $icon = $link['icon'] ?? 'dashboard';
                        @endphp
                        <li>
                            <a
                                href="{{ $href }}"
                                wire:navigate
                                @class([
                                    'profile-sidebar-link',
                                    'profile-sidebar-link-active' => $isActive,
                                ])
                            >
                                <x-nav-icon :name="$icon" class="h-4 w-4 shrink-0 opacity-70" />
                                <span class="truncate">{{ $link['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>
@endif