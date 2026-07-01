@php
    $links = collect(config('navigation.settings', []))->filter(
        fn ($link) => empty($link['permission']) || auth()->user()?->can($link['permission'])
    );
@endphp

@if ($links->isNotEmpty())
    <nav class="flex gap-1 overflow-x-auto border-b border-zinc-200 pb-px">
        @foreach ($links as $link)
            @php $active = request()->is(ltrim($link['href'], '/').'*'); @endphp
            <a
                href="{{ $link['href'] }}"
                class="shrink-0 border-b-2 px-3 py-2 text-sm font-medium transition {{ $active ? 'border-zinc-900 text-zinc-900' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}"
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
@endif
