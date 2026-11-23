@props(['summary' => []])

@php
    $items = [
        ['label' => 'Site tours', 'key' => 'site_tours', 'route' => 'patrols.index', 'permission' => 'patrols.manage'],
        ['label' => 'Tasks', 'key' => 'tasks', 'route' => 'patrols.index', 'permission' => 'patrols.manage'],
        ['label' => 'Checklists', 'key' => 'checklists', 'route' => 'patrols.index', 'permission' => 'patrols.manage'],
        ['label' => 'Check-ins', 'key' => 'check_ins', 'route' => 'schedules.attendance', 'permission' => 'attendance.manage'],
        ['label' => 'Passdowns', 'key' => 'passdowns', 'route' => 'passdown.index', 'permission' => 'patrols.manage'],
        ['label' => 'Idle alerts', 'key' => 'idle_alerts', 'route' => 'tracking.live', 'permission' => 'dispatch.manage'],
    ];
    $nav = app(\App\Services\NavigationBuilder::class);
    $items = array_filter($items, fn ($item) => $nav->routeVisible($item['route'], $item['permission']));
@endphp

@if ($items)
<section class="card-surface overflow-hidden">
    <div class="card-header">
        <div>
            <h2 class="card-header-title">Activity summary</h2>
            <p class="card-header-meta">Last 7 days</p>
        </div>
    </div>
    <div class="grid grid-cols-3 gap-px bg-zinc-100 dark:bg-zinc-800">
        @foreach ($items as $item)
            <a href="{{ route($item['route']) }}" class="bg-white px-2 py-3 text-center transition hover:bg-zinc-50 dark:bg-zinc-900 dark:hover:bg-zinc-800/80">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-zinc-400">{{ $item['label'] }}</p>
                <p class="mt-1 text-xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ str_pad((string) ($summary[$item['key']] ?? 0), 2, '0', STR_PAD_LEFT) }}</p>
            </a>
        @endforeach
    </div>
</section>
@endif
