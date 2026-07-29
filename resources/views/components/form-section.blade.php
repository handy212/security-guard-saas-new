@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'sm:col-span-2']) }}>
    <div class="mb-4 border-b border-zinc-100 pb-3 dark:border-zinc-800">
        <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $title }}</h3>
        @if ($description)
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
        @endif
    </div>
    <div class="drawer-form-fields">
        {{ $slot }}
    </div>
</div>
