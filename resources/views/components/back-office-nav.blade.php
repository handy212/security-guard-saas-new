@php
    $nav = app(\App\Services\NavigationBuilder::class);
    $links = collect(config('navigation.billing', []))
        ->filter(fn ($link) => $nav->linkVisible($link))
        ->map(function ($link) {
            $href = ltrim($link['href'], '/');
            $isOverview = $href === 'billing';

            $active = match (true) {
                $isOverview => request()->is('billing') && ! request()->is('billing/*'),
                str_starts_with($href, 'compliance') => request()->is('compliance*'),
                $href === 'analytics' => request()->is('analytics'),
                default => request()->is($href) || request()->is($href.'/*'),
            };

            return [
                'href' => $link['href'],
                'label' => $link['label'],
                'icon' => $link['icon'] ?? 'billing',
                'group' => 'Back Office',
                'active' => $active,
            ];
        })
        ->values()
        ->all();
@endphp

<x-sub-sidebar :links="$links" />
