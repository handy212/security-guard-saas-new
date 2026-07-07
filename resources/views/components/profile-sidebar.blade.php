@props([
    'tabs' => [],
    'active' => '',
    'action' => 'setTab',
])

@php
    $groups = [];
    foreach ($tabs as $key => $tab) {
        $group = is_array($tab) ? ($tab['group'] ?? 'General') : 'General';
        $groups[$group][$key] = $tab;
    }

    $defaultIcons = [
        'overview' => 'dashboard',
        'profile' => 'clients',
        'contacts' => 'workforce',
        'notes' => 'messenger',
        'files' => 'reports',
        'sites' => 'sites',
        'portal' => 'clients',
        'users' => 'workforce',
        'reports' => 'reports',
        'availability' => 'schedules',
        'kpis' => 'analytics',
        'licenses' => 'compliance',
        'reminders' => 'schedules',
        'skills' => 'guards',
        'department' => 'clients',
        'settings' => 'settings',
        'post_orders' => 'reports',
        'guards' => 'guards',
        'tasks' => 'check',
        'tours' => 'patrols',
        'tour_tags' => 'gps',
        'geofence' => 'gps',
        'checklists' => 'compliance',
        'email_reports' => 'reports',
    ];
@endphp

<nav {{ $attributes->merge(['class' => 'profile-sidebar']) }} aria-label="Profile sections">
    @foreach ($groups as $groupLabel => $groupTabs)
        <div class="profile-sidebar-group">
            <p class="profile-sidebar-heading">{{ $groupLabel }}</p>
            <ul class="space-y-0.5">
                @foreach ($groupTabs as $key => $tab)
                    @php
                        $label = is_array($tab) ? ($tab['label'] ?? $key) : $tab;
                        $badge = is_array($tab) ? ($tab['badge'] ?? null) : null;
                        $hint = is_array($tab) ? ($tab['hint'] ?? null) : null;
                        $icon = is_array($tab) ? ($tab['icon'] ?? ($defaultIcons[$key] ?? 'dashboard')) : ($defaultIcons[$key] ?? 'dashboard');
                        $isActive = $active === $key;
                    @endphp
                    <li>
                        <button
                            type="button"
                            wire:click="{{ $action }}('{{ $key }}')"
                            title="{{ $hint }}"
                            @class([
                                'profile-sidebar-link w-full',
                                'profile-sidebar-link-active' => $isActive,
                            ])
                        >
                            <x-nav-icon :name="$icon" class="h-4 w-4 shrink-0 opacity-70" />
                            <span class="truncate">{{ $label }}</span>
                            @if ($badge)
                                <span @class([
                                    'profile-sidebar-badge',
                                    'profile-sidebar-badge-active' => $isActive,
                                ])>{{ $badge }}</span>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
