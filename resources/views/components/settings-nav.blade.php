@php
    $links = collect(config('navigation.settings', []))->filter(
        fn ($link) => empty($link['permission']) || auth()->user()?->can($link['permission'])
    );
@endphp

@if ($links->isNotEmpty())
    <nav class="subnav-bar">
        @foreach ($links as $link)
            @php $active = request()->is(ltrim($link['href'], '/').'*'); @endphp
            <a href="{{ $link['href'] }}" class="subnav-link {{ $active ? 'subnav-link-active' : '' }}">
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
@endif
