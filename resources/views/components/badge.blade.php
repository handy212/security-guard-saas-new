@props(['status', 'map' => []])

@php
    $tone = $map[$status] ?? match(strtolower((string) $status)) {
        'active', 'completed', 'approved', 'closed', 'on_time', 'valid', 'verified' => 'success',
        'open', 'assigned', 'in_progress', 'submitted', 'trial' => 'info',
        'late', 'pending', 'acknowledged', 'partial', 'unverified' => 'warning',
        'inactive', 'rejected', 'missed', 'no_show', 'failed', 'past_due', 'suspended', 'expired' => 'danger',
        default => 'neutral',
    };
    $styles = [
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'info' => 'bg-zinc-100 text-zinc-700 ring-zinc-600/10',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'danger' => 'bg-red-50 text-red-700 ring-red-600/20',
        'neutral' => 'bg-zinc-100 text-zinc-600 ring-zinc-600/10',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded px-1.5 py-0.5 text-[11px] font-medium ring-1 ring-inset '.($styles[$tone] ?? $styles['neutral'])]) }}>
    {{ ucfirst(str_replace('_', ' ', (string) $status)) }}
</span>
