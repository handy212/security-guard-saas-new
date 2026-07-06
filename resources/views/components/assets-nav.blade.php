@php
    $links = collect(config('navigation.assets', []))->filter(
        fn ($link) => empty($link['permission']) || auth()->user()?->can($link['permission'])
    );
@endphp

@if ($links->isNotEmpty())
    <nav class="subnav-bar">
        @foreach ($links as $link)
            @php
                $href = ltrim($link['href'], '/');
                $active = $href === 'assets'
                    ? request()->is('assets') && ! request()->is('assets/*')
                    : request()->is($href.'*');
            @endphp
            <a href="{{ $link['href'] }}" class="subnav-link {{ $active ? 'subnav-link-active' : '' }}">
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
@endif
