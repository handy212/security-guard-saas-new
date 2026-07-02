@props(['name' => 'dashboard', 'class' => 'h-[18px] w-[18px]'])

@php
    $path = \App\Support\NavigationIcons::path($name);
@endphp

<svg {{ $attributes->merge(['class' => $class]) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
</svg>
