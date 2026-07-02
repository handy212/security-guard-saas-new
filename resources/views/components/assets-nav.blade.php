@php
    $links = collect(config('navigation.assets', []))->filter(
        fn ($link) => empty($link['permission']) || auth()->user()?->can($link['permission'])
    );
@endphp

@if ($links->isNotEmpty())
    <nav class="mb-4 flex gap-1 overflow-x-auto border-b border-zinc-200 pb-px">
        @foreach ($links as $link)
            @php
                $href = ltrim($link['href'], '/');
                $active = $href === 'assets'
                    ? request()->is('assets') && ! request()->is('assets/*')
                    : request()->is($href.'*');
            @endphp
            <a
                href="{{ $link['href'] }}"
                class="shrink-0 border-b-2 px-3 py-2 text-sm font-medium transition {{ $active ? 'border-zinc-900 text-zinc-900' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}"
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
@endif
