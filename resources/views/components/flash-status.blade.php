@props(['type' => 'info'])

@php
    $styles = [
        'info' => 'border-sky-200 bg-sky-50 text-sky-900',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        'error' => 'border-red-200 bg-red-50 text-red-900',
    ];
@endphp

@if(session('status'))
    <div {{ $attributes->merge(['class' => 'border px-4 py-3 text-sm '.($styles[$type] ?? $styles['info'])]) }} role="alert">
        <div class="flex items-center gap-2">
            @if($type === 'success')
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            @endif
            {{ session('status') }}
        </div>
    </div>
@endif
