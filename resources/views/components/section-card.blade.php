@props(['title', 'description' => null, 'flush' => false, 'compact' => false])

<section {{ $attributes->merge(['class' => 'card-surface flex min-w-0 flex-col overflow-hidden']) }}>
    <div @class(['card-header shrink-0', 'px-4 py-2.5' => $compact])>
        <div class="min-w-0">
            <h2 class="card-header-title">{{ $title }}</h2>
            @if($description)
                <p class="card-header-meta">{{ $description }}</p>
            @endif
        </div>
        @if (isset($actions))
            <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
        @endif
    </div>
    <div @class(['min-h-0 flex-1', $flush ? 'p-0' : ($compact ? 'px-4 py-3' : 'p-5 sm:p-6')])>
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="form-card-footer shrink-0">{{ $footer }}</div>
    @endisset
</section>
