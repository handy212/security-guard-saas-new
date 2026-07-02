@props(['summary' => []])

@php
    $items = [
        ['label' => 'Site tours', 'key' => 'site_tours'],
        ['label' => 'Tasks', 'key' => 'tasks'],
        ['label' => 'Checklists', 'key' => 'checklists'],
        ['label' => 'Check-ins', 'key' => 'check_ins'],
        ['label' => 'Passdowns', 'key' => 'passdowns'],
        ['label' => 'Idle alerts', 'key' => 'idle_alerts'],
    ];
@endphp

<section class="card-surface overflow-hidden p-5">
    <div class="mb-4">
        <h2 class="text-sm font-semibold text-zinc-900">Activity summary</h2>
        <p class="text-xs text-zinc-500">Last 7 days</p>
    </div>
    <div class="grid grid-cols-3 gap-3">
        @foreach ($items as $item)
            <div class="text-center">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-zinc-400">{{ $item['label'] }}</p>
                <p class="mt-1 text-xl font-bold text-zinc-900">{{ str_pad((string) ($summary[$item['key']] ?? 0), 2, '0', STR_PAD_LEFT) }}</p>
            </div>
        @endforeach
    </div>
</section>
