@php
    $nav = app(\App\Services\NavigationBuilder::class);
    $settingsLinks = $nav->settingsLinks();

    $links = $settingsLinks
        ->map(function ($link) {
            $href = ltrim($link['href'], '/');
            $isHub = $href === 'settings';

            return [
                'href' => $link['href'],
                'label' => $link['label'],
                'icon' => $link['icon'] ?? match (true) {
                    str_contains($href, 'billing/subscription') => 'subscriptions',
                    str_contains($href, 'id-card') => 'guards',
                    str_contains($href, 'know-your-guard') => 'guards',
                    str_contains($href, 'roles') => 'workforce',
                    str_contains($href, 'audit') => 'reports',
                    str_contains($href, 'team') => 'workforce',
                    str_contains($href, 'two-factor') => 'compliance',
                    str_contains($href, 'webhooks') => 'dispatch',
                    str_contains($href, 'offline-sync') => 'mobile',
                    default => 'settings',
                },
                'group' => 'Settings',
                'active' => str_contains($href, 'billing/subscription')
                    ? request()->is('billing/subscription*')
                    : ($isHub
                        ? request()->is('settings') && ! request()->is('settings/*')
                        : request()->is($href) || request()->is($href.'/*')),
            ];
        })
        ->prepend([
            'href' => route('settings.index'),
            'label' => 'All settings',
            'icon' => 'dashboard',
            'group' => 'Settings',
            'active' => request()->is('settings') && ! request()->is('settings/*'),
        ])
        ->values()
        ->all();
@endphp

<x-sub-sidebar :links="$links" />
