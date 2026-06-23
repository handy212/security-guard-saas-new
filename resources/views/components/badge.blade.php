@props(['status', 'map' => []])

@php
    $tone = $map[$status] ?? match(strtolower((string) $status)) {
        'active', 'completed', 'approved', 'closed', 'on_time', 'valid' => 'success',
        'open', 'assigned', 'in_progress', 'submitted', 'trial' => 'info',
        'late', 'pending', 'acknowledged', 'partial' => 'warning',
        'inactive', 'rejected', 'missed', 'no_show', 'failed', 'past_due' => 'danger',
        default => 'neutral',
    };
    $styles = [
        'success' => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
        'info' => 'bg-sky-100 text-sky-800 ring-sky-600/20',
        'warning' => 'bg-amber-100 text-amber-800 ring-amber-600/20',
        'danger' => 'bg-red-100 text-red-800 ring-red-600/20',
        'neutral' => 'bg-slate-100 text-slate-700 ring-slate-600/10',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset '.($styles[$tone] ?? $styles['neutral'])]) }}>
    {{ ucfirst(str_replace('_', ' ', (string) $status)) }}
</span>
