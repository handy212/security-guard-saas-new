@props(['summary' => []])

@php
    $items = [
        ['label' => 'Site tours', 'key' => 'site_tours', 'href' => route('patrols.index')],
        ['label' => 'Tasks', 'key' => 'tasks', 'href' => route('patrols.index')],
        ['label' => 'Checklists', 'key' => 'checklists', 'href' => route('patrols.index')],
        ['label' => 'Check-ins', 'key' => 'check_ins', 'href' => route('schedules.attendance')],
        ['label' => 'Passdowns', 'key' => 'passdowns', 'href' => route('reports.daily')],
        ['label' => 'Idle alerts', 'key' => 'idle_alerts', 'href' => route('tracking.live')],
    ];
@endphp

<section class="card-surface overflow-hidden p-5">
    <div class="mb-4">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Activity summary</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">Last 7 days · tap a metric to open</p>
    </div>
    <div class="grid grid-cols-3 gap-3">
        @foreach ($items as $item)
            <a href="{{ $item['href'] }}" class="rounded-lg px-1 py-2 text-center transition hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-zinc-400">{{ $item['label'] }}</p>
                <p class="mt-1 text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ str_pad((string) ($summary[$item['key']] ?? 0), 2, '0', STR_PAD_LEFT) }}</p>
            </a>
        @endforeach
    </div>
</section>
