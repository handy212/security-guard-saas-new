@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div class="min-w-0">
        <h1 class="text-lg font-semibold text-zinc-900">{{ $title }}</h1>
        @if ($description)
            <p class="mt-0.5 text-sm text-zinc-500">{{ $description }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>
