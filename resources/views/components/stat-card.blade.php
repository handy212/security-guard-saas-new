@props(['label', 'value', 'hint' => null, 'tone' => 'default'])

@php
    $tones = [
        'default' => 'bg-white text-slate-900',
        'success' => 'bg-emerald-50 text-emerald-900',
        'warning' => 'bg-amber-50 text-amber-900',
        'danger' => 'bg-red-50 text-red-900',
        'info' => 'bg-sky-50 text-sky-900',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border p-4 '.$tones[$tone] ?? $tones['default']]) }}>
    <div class="text-sm font-medium text-slate-500">{{ $label }}</div>
    <div class="mt-1 text-3xl font-black tracking-tight">{{ $value }}</div>
    @if ($hint)
        <div class="mt-1 text-xs text-slate-500">{{ $hint }}</div>
    @endif
</div>
