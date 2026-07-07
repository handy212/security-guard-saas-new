@props([
    'tabs' => [],
    'active' => '',
    'action' => 'setTab',
])

@php
    $activeTab = $tabs[$active] ?? null;
    $activeLabel = is_array($activeTab) ? ($activeTab['label'] ?? $active) : ($activeTab ?? $active);
    $activeHint = is_array($activeTab) ? ($activeTab['hint'] ?? null) : null;
@endphp

<div class="profile-layout">
    {{-- Mobile tab picker --}}
    <div class="profile-mobile-nav lg:hidden">
        <label class="sr-only" for="profile-tab-select">Section</label>
        <select id="profile-tab-select" wire:model.live="activeTab" class="form-input text-sm">
            @foreach ($tabs as $key => $tab)
                <option value="{{ $key }}" @selected($active === $key)>
                    {{ is_array($tab) ? ($tab['label'] ?? $key) : $tab }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="profile-layout-grid">
        <aside class="profile-layout-aside">
            <div class="profile-layout-aside-inner">
                <x-profile-sidebar :tabs="$tabs" :active="$active" :action="$action" />
            </div>
        </aside>

        <div class="profile-layout-main">
            <header class="profile-tab-header">
                <div>
                    <h2 class="profile-tab-title">{{ $activeLabel }}</h2>
                    @if ($activeHint)
                        <p class="profile-tab-description">{{ $activeHint }}</p>
                    @endif
                </div>
                @if (isset($headerActions))
                    <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $headerActions }}</div>
                @endif
            </header>

            <div class="profile-tab-content">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
