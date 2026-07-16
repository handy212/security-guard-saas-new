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
        'success' => 'bg-emerald-50 text-emerald-800 ring-emerald-600/15 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/20',
        'info' => 'bg-accent-50 text-accent-800 ring-accent-600/15 dark:bg-accent-950/40 dark:text-accent-300 dark:ring-accent-500/20',
        'warning' => 'bg-amber-50 text-amber-800 ring-amber-600/15 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-500/20',
        'danger' => 'bg-red-50 text-red-800 ring-red-600/15 dark:bg-red-950/40 dark:text-red-300 dark:ring-red-500/20',
        'neutral' => 'bg-zinc-100 text-zinc-600 ring-zinc-500/10 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-500/20',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold tracking-wide ring-1 ring-inset '.($styles[$tone] ?? $styles['neutral'])]) }}>
    {{ ucfirst(str_replace('_', ' ', $statusValue)) }}
</span>
