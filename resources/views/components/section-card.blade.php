@props(['title', 'description' => null])

<section {{ $attributes->merge(['class' => 'card-surface flex h-full flex-col p-4']) }}>
    <div class="mb-3 shrink-0">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</h2>
        @if($description)
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
        @endif
    </div>
    <div class="min-h-0 flex-1">
        {{ $slot }}
    </div>
</section>
