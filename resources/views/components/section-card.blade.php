@props(['title', 'description' => null])

<section {{ $attributes->merge(['class' => 'rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900']) }}>
    <div class="mb-3">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</h2>
        @if($description)
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
        @endif
    </div>
    {{ $slot }}
</section>
