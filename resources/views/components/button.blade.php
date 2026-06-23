@props(['variant' => 'primary', 'size' => 'md', 'type' => 'button'])

@php
    $classes = match($variant) {
        'primary' => 'btn-primary',
        'secondary' => 'btn-secondary',
        'danger' => 'btn-danger',
        'link' => 'btn-link',
        default => 'btn-primary',
    };
    $sizeClass = $size === 'sm' ? 'px-3 py-1.5 text-xs' : '';
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => trim($classes.' '.$sizeClass)]) }}>
    <span wire:loading.remove wire:target="{{ $attributes->get('wire:click') ?? $attributes->get('wire:submit') }}">{{ $slot }}</span>
    <span wire:loading wire:target="{{ $attributes->get('wire:click') ?? $attributes->get('wire:submit') }}" class="inline-flex items-center gap-1">
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        Working…
    </span>
</button>
