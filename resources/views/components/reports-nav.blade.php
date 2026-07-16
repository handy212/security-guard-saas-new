@php
    $nav = app(\App\Services\NavigationBuilder::class);
    $links = collect(config('navigation.reports', []))
        ->filter(fn ($link) => $nav->linkVisible($link))
        ->map(function ($link) {
            $href = ltrim($link['href'], '/');
            $isOverview = $href === 'reports';

            return [
                'href' => $link['href'],
                'label' => $link['label'],
                'icon' => $link['icon'] ?? 'reports',
                'group' => 'Reports',
                'active' => $isOverview
                    ? request()->is('reports') && ! request()->is('reports/*')
                    : request()->is($href) || request()->is($href.'/*'),
            ];
        })
        ->values()
        ->all();
@endphp

<x-sub-sidebar :links="$links" />
