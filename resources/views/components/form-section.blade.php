@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'sm:col-span-2']) }}>
    <div class="mb-3 border-b border-zinc-100 pb-2 dark:border-zinc-800">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $title }}</h3>
        @if ($description)
            <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">{{ $description }}</p>
        @endif
    </div>
    <div class="drawer-form-fields">
        {{ $slot }}
    </div>
</div>
