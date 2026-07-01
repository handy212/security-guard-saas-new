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
    {{ $slot }}
</button>
