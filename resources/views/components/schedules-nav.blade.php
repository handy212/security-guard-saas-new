@php
    $nav = app(\App\Services\NavigationBuilder::class);
    $links = collect(config('navigation.schedules', []))->filter(fn ($link) => $nav->linkVisible($link));
@endphp

@if ($links->isNotEmpty())
    <nav class="subnav-bar">
        @foreach ($links as $link)
            @php
                $href = ltrim($link['href'], '/');
                $active = in_array($href, ['schedules', 'schedule'], true)
                    ? request()->is('schedules') && ! request()->is('schedules/*')
                    : request()->is($href) || request()->is($href.'/*');
            @endphp
            <a href="{{ $link['href'] }}" class="subnav-link {{ $active ? 'subnav-link-active' : '' }}">
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
@endif
