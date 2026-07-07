@php
    $nav = app(\App\Services\NavigationBuilder::class);
    $links = collect(config('navigation.schedules', []))
        ->filter(fn ($link) => $nav->linkVisible($link))
        ->map(function ($link) {
            $href = ltrim($link['href'], '/');
            $isIndex = in_array($href, ['schedules', 'schedule'], true);

            return [
                'href' => $link['href'],
                'label' => $link['label'],
                'icon' => $link['icon'] ?? 'schedules',
                'group' => 'Schedules',
                'active' => $isIndex
                    ? request()->is('schedules') && ! request()->is('schedules/*')
                    : request()->is($href) || request()->is($href.'/*'),
            ];
        })
        ->values()
        ->all();
@endphp

<x-sub-sidebar :links="$links" />
