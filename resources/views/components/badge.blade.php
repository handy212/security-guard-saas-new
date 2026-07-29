@props(['status', 'map' => []])

@php
    $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $tone = $map[$statusValue] ?? match(strtolower($statusValue)) {
        'active', 'completed', 'approved', 'closed', 'on_time', 'valid', 'verified', 'paid' => 'success',
        'open', 'assigned', 'in_progress', 'submitted', 'trial', 'sent' => 'info',
        'late', 'pending', 'acknowledged', 'partial', 'unverified', 'overdue' => 'warning',
        'inactive', 'rejected', 'missed', 'no_show', 'failed', 'past_due', 'suspended', 'expired', 'void' => 'danger',
        default => 'neutral',
    };
    $styles = [
        'success' => 'border-emerald-200/80 bg-emerald-50 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-300',
        'info' => 'border-accent-200/80 bg-accent-50 text-accent-800 dark:border-accent-800/50 dark:bg-accent-950/40 dark:text-accent-300',
        'warning' => 'border-amber-200/80 bg-amber-50 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-300',
        'danger' => 'border-red-200/80 bg-red-50 text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300',
        'neutral' => 'border-zinc-200/80 bg-zinc-50 text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium '.($styles[$tone] ?? $styles['neutral'])]) }}>
    {{ ucfirst(str_replace('_', ' ', $statusValue)) }}
</span>
