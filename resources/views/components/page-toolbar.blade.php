@props(['search' => null, 'searchPlaceholder' => 'Search…'])

<div {{ $attributes->merge(['class' => 'panel-surface']) }}>
    <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:gap-3">
        @if (isset($tabs))
            <div class="min-w-0 shrink-0 overflow-x-auto">{{ $tabs }}</div>
        @endif

        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2 sm:justify-end">
            @if ($search !== null)
                <div class="w-full min-w-[12rem] flex-1 sm:max-w-xs">
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
</div>
