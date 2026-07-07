@php
    $links = collect(config('navigation.assets', []))
        ->filter(fn ($link) => empty($link['permission']) || auth()->user()?->can($link['permission']))
        ->map(function ($link) {
            $href = ltrim($link['href'], '/');
            $isOverview = $href === 'assets';

            return [
                'href' => $link['href'],
                'label' => $link['label'],
                'icon' => $link['icon'] ?? 'equipment',
                'group' => 'Assets',
                'active' => $isOverview
                    ? request()->is('assets') && ! request()->is('assets/*')
                    : request()->is($href) || request()->is($href.'/*'),
            ];
        })
        ->values()
        ->all();
@endphp

<x-sub-sidebar :links="$links" />
