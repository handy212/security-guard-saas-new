@props([
    'tabs' => [],
    'active' => '',
    'action' => 'setTab',
])

@php
    $activeTab = $tabs[$active] ?? null;
    $activeLabel = is_array($activeTab) ? ($activeTab['label'] ?? $active) : ($activeTab ?? $active);
    $activeHint = is_array($activeTab) ? ($activeTab['hint'] ?? null) : null;
    $hasHeaderActions = isset($headerActions);
@endphp

<div class="profile-layout">
    {{-- Mobile tab picker --}}
    <div class="profile-mobile-nav lg:hidden">
        <label class="sr-only" for="profile-tab-select">Section</label>
        <select id="profile-tab-select" wire:model.live="activeTab" class="form-input text-sm">
            @php
                $mobileGroups = [];
                foreach ($tabs as $key => $tab) {
                    $group = is_array($tab) ? ($tab['group'] ?? 'General') : 'General';
                    $mobileGroups[$group][$key] = $tab;
                }
            @endphp
            @foreach ($mobileGroups as $groupLabel => $groupTabs)
                <optgroup label="{{ $groupLabel }}">
                    @foreach ($groupTabs as $key => $tab)
                        <option value="{{ $key }}" @selected($active === $key)>
                            {{ is_array($tab) ? ($tab['label'] ?? $key) : $tab }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @if ($activeHint)
            <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $activeHint }}</p>
        @endif
    </div>

    <div class="profile-layout-grid">
        <aside class="profile-layout-aside">
            <div class="profile-layout-aside-inner">
                <x-profile-sidebar :tabs="$tabs" :active="$active" :action="$action" />
            </div>
        </aside>

        <div class="profile-layout-main">
            @if ($hasHeaderActions)
                <header class="profile-tab-header">
                    <div class="min-w-0 lg:hidden">
                        <h2 class="profile-tab-title">{{ $activeLabel }}</h2>
                        @if ($activeHint)
                            <p class="profile-tab-description">{{ $activeHint }}</p>
                        @endif
                    </div>
                    <div class="ml-auto flex shrink-0 flex-wrap items-center gap-2">{{ $headerActions }}</div>
                </header>
            @endif

            <div class="profile-tab-content">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
