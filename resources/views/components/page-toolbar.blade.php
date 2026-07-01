@props(['search' => null, 'searchPlaceholder' => 'Search…'])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-3 lg:flex-row lg:items-center lg:justify-between']) }}>
    @if (isset($tabs))
        <div class="shrink-0">{{ $tabs }}</div>
    @endif

    <div class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center sm:justify-end lg:gap-3">
        @if ($search !== null)
            <div class="w-full sm:max-w-xs lg:max-w-sm">
                <x-search-input wire:model.live.debounce.300ms="{{ $search }}" placeholder="{{ $searchPlaceholder }}" />
            </div>
        @endif
        @if (isset($controls))
            <div class="flex flex-wrap items-center gap-2">{{ $controls }}</div>
        @endif
        @if (isset($actions))
            <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
        @endif
    </div>
</div>
