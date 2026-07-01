@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'default',
    'href' => null,
    'icon' => null,
])

@php
    $tones = [
        'default' => ['ring' => 'ring-zinc-200', 'icon' => 'bg-zinc-100 text-zinc-600'],
        'success' => ['ring' => 'ring-emerald-200', 'icon' => 'bg-emerald-50 text-emerald-600'],
        'warning' => ['ring' => 'ring-amber-200', 'icon' => 'bg-amber-50 text-amber-600'],
        'danger' => ['ring' => 'ring-red-200', 'icon' => 'bg-red-50 text-red-600'],
        'info' => ['ring' => 'ring-zinc-200', 'icon' => 'bg-zinc-100 text-zinc-600'],
    ];
    $style = $tones[$tone] ?? $tones['default'];

    $icons = [
        'guards' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'shifts' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'incidents' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        'patrols' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
        'sos' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    ];
    $path = $icons[$icon] ?? $icons['guards'];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'group block rounded-xl border border-zinc-200 bg-white p-4 shadow-sm ring-1 ring-inset '.$style['ring'].' transition hover:border-zinc-300 hover:shadow-md']) }}>
@else
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-zinc-200 bg-white p-4 shadow-sm ring-1 ring-inset '.$style['ring']]) }}>
@endif
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ $label }}</p>
            <p class="mt-1 text-3xl font-semibold tracking-tight text-zinc-900">{{ $value }}</p>
            @if ($hint)
                <p class="mt-1 text-xs text-zinc-500">{{ $hint }}</p>
            @endif
        </div>
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $style['icon'] }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
            </svg>
        </div>
    </div>
@if ($href)
    </a>
@else
    </div>
@endif
