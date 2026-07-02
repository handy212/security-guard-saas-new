@props(['href' => null, 'danger' => false, 'type' => 'button'])

@php
    $class = $danger ? 'table-row-menu-item-danger' : 'table-row-menu-item';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }} @click="open = false">
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }} @click="open = false">
        {{ $slot }}
    </button>
@endif
