@props(['status', 'map' => []])

@php
    $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $tone = $map[$statusValue] ?? match(strtolower($statusValue)) {
        'active', 'completed', 'approved', 'closed', 'on_time', 'valid', 'verified' => 'success',
        'open', 'assigned', 'in_progress', 'submitted', 'trial' => 'info',
        'late', 'pending', 'acknowledged', 'partial', 'unverified' => 'warning',
        'inactive', 'rejected', 'missed', 'no_show', 'failed', 'past_due', 'suspended', 'expired' => 'danger',
        default => 'neutral',
    };
    $styles = [
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-900/30 dark:text-emerald-300',
        'info' => 'bg-zinc-100 text-zinc-700 ring-zinc-600/10 dark:bg-zinc-800 dark:text-zinc-300',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-900/30 dark:text-amber-300',
        'danger' => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-300',
        'neutral' => 'bg-zinc-100 text-zinc-600 ring-zinc-600/10 dark:bg-zinc-800 dark:text-zinc-400',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded px-1.5 py-0.5 text-[11px] font-medium ring-1 ring-inset '.($styles[$tone] ?? $styles['neutral'])]) }}>
    {{ ucfirst(str_replace('_', ' ', $statusValue)) }}
</span>
