@props(['title', 'description' => null, 'flush' => false])

<section {{ $attributes->merge(['class' => 'card-surface flex h-full flex-col overflow-hidden']) }}>
    <div class="card-header shrink-0">
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
    <div @class(['min-h-0 flex-1', $flush ? 'p-0' : 'p-4'])>
        {{ $slot }}
    </div>
</section>
