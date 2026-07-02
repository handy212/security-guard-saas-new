@props(['variant' => 'primary', 'size' => 'md', 'type' => 'button', 'href' => null, 'loadingText' => null])

@php
    $classes = match($variant) {
        'primary' => 'btn-primary',
        'secondary' => 'btn-secondary',
        'danger' => 'btn-danger',
        'link' => 'btn-link',
        default => 'btn-primary',
    };
    $sizeClass = match($size) {
        'sm' => '!px-2.5 !py-1 text-xs',
        'lg' => '!px-4 !py-2 text-sm',
        default => '',
    };
    $merged = trim($classes.' '.$sizeClass);
    $loadingLabel = $loadingText ?? ($type === 'submit' ? 'Saving…' : null);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $merged]) }}>
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        wire:loading.attr="disabled"
        {{ $attributes->merge(['class' => $merged]) }}
    >
        @if ($loadingLabel)
            <span wire:loading.remove>{{ $slot }}</span>
            <span wire:loading>{{ $loadingLabel }}</span>
        @else
            {{ $slot }}
        @endif
    </button>
@endif
