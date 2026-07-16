@props(['title', 'description' => null, 'flush' => false])

<section {{ $attributes->merge(['class' => 'card-surface flex h-full flex-col overflow-hidden']) }}>
    <div class="shrink-0 border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
        <h2 class="text-sm font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $title }}</h2>
        @if($description)
            <p class="mt-0.5 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
        @endif
    </div>
    <div @class(['min-h-0 flex-1', $flush ? 'p-0' : 'p-4'])>
        {{ $slot }}
    </div>
</section>
