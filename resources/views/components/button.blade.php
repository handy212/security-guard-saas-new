@props(['variant' => 'primary', 'size' => 'md', 'type' => 'button', 'href' => null])

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
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $merged]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $merged]) }}>
        {{ $slot }}
    </button>
@endif
