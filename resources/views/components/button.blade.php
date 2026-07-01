@props(['variant' => 'primary', 'size' => 'md', 'type' => 'button', 'loadingText' => null])

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

<button
    type="{{ $type }}"
    wire:loading.attr="disabled"
    {{ $attributes->merge(['class' => trim($classes.' '.$sizeClass)]) }}
>
    @if ($loadingText)
        <span wire:loading.remove>{{ $slot }}</span>
        <span wire:loading>{{ $loadingText }}</span>
    @else
        {{ $slot }}
    @endif
</button>
