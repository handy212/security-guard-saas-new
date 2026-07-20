<div>
    <x-page-shell title="Calendar" description="Plan shifts by week or month, then open a day to staff them.">
        <x-slot:actions>
            <x-button variant="secondary" size="sm" :href="route('schedules.index')">Day roster</x-button>
            <x-button size="sm" :href="route('schedules.index', ['date' => today()->toDateString()])">Create for today</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>

            <div class="stat-grid">
                <x-stat-card compact label="In range" :value="$stats['total']" icon="schedules" />
                <x-stat-card compact label="Open" :value="$stats['open']" icon="pause" :tone="$stats['open'] > 0 ? 'warning' : 'default'" />
                <x-stat-card compact label="Posts" :value="$stats['posts']" icon="plan" tone="info" />
                <x-stat-card compact label="View" :value="ucfirst($view)" icon="check" />
            </div>

            <x-page-toolbar>
                <x-slot:tabs>
                    <x-segment-control field="view" :active="$view" :options="['month' => 'Month', 'week' => 'Week']" />
                </x-slot:tabs>
                <x-slot:controls>
                    <div class="date-nav">
                        <x-filter-select wire:model.live="siteId">
                            <option value="">All sites</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->name }}</option>
                            @endforeach
                        </x-filter-select>
                        <x-button type="button" variant="secondary" wire:click="previous" size="sm">Prev</x-button>
                        <x-button type="button" variant="secondary" wire:click="next" size="sm">Next</x-button>
                    </div>
                </x-slot:controls>
            </x-page-toolbar>

            {{-- Mobile: agenda list --}}
            <div class="space-y-2 sm:hidden">
                @php $day = $rangeStart->copy(); @endphp
                @while ($day <= $rangeEnd)
                    @php $dayShifts = $shifts->filter(fn ($s) => $s->starts_at->isSameDay($day)); @endphp
                    @if ($dayShifts->isNotEmpty())
                        <section class="card-surface overflow-hidden">
                            <div class="card-header py-2.5">
                                <h3 class="card-header-title text-xs uppercase tracking-wide text-zinc-500">{{ $day->format('D, M j') }}</h3>
                                <span class="text-xs tabular-nums text-zinc-400">{{ $dayShifts->count() }}</span>
                            </div>
                            @foreach ($dayShifts as $shift)
                                @php $under = $shift->activeAssignmentsCount() < $shift->required_guards; @endphp
                                <a
                                    href="{{ route('schedules.index', ['date' => $shift->starts_at->toDateString()]) }}"
                                    @class([
                                        'list-row text-sm',
                                        'bg-amber-50/50 dark:bg-amber-950/20' => $under,
                                    ])
                                >
                                    <span class="min-w-0 flex-1">
                                        <span class="font-medium tabular-nums text-zinc-900 dark:text-zinc-100">{{ $shift->starts_at->format('H:i') }}</span>
                                        <span class="text-zinc-500"> · {{ $shift->site?->name }}</span>
                                    </span>
                                    <span @class([
                                        'text-xs tabular-nums font-medium',
                                        'text-amber-700 dark:text-amber-400' => $under,
                                        'text-zinc-500' => ! $under,
                                    ])>{{ $shift->activeAssignmentsCount() }}/{{ $shift->required_guards }}</span>
                                </a>
                            @endforeach
                        </section>
                    @endif
                    @php $day->addDay(); @endphp
                @endwhile
                @if ($shifts->isEmpty())
                    <x-empty-state
                        title="No shifts in range"
                        description="Create a shift on the day roster, or open a day to staff coverage."
                    >
                        <x-slot:actions>
                            <x-button size="sm" :href="route('schedules.index', ['date' => today()->toDateString()])">Create for today</x-button>
                            <x-button size="sm" variant="secondary" :href="route('schedules.index')">Day roster</x-button>
                        </x-slot:actions>
                    </x-empty-state>
                @endif
            </div>

            {{-- Desktop: calendar grid --}}
            <div class="hidden overflow-hidden rounded-[var(--radius-card)] border border-zinc-200/90 bg-zinc-100/80 dark:border-zinc-800 dark:bg-zinc-950 sm:grid sm:grid-cols-7 sm:gap-px">
                @php
                    $day = $rangeStart->copy();
                    $today = today();
                @endphp
                @while ($day <= $rangeEnd)
                    @php
                        $dayShifts = $shifts->filter(fn ($s) => $s->starts_at->isSameDay($day));
                        $inMonth = $view === 'week' || $day->month === \Carbon\Carbon::parse($cursorDate)->month;
                        $isToday = $day->isSameDay($today);
                    @endphp
                    <div @class([
                        'min-h-28 p-2',
                        'bg-white dark:bg-zinc-900' => $inMonth,
                        'bg-zinc-50 text-zinc-400 dark:bg-zinc-950/80 dark:text-zinc-600' => ! $inMonth,
                        'ring-2 ring-inset ring-accent-500/40' => $isToday,
                    ])>
                        <div class="flex items-center justify-between gap-1">
                            <div @class([
                                'text-xs font-semibold tabular-nums',
                                'text-accent-700 dark:text-accent-300' => $isToday,
                            ])>{{ $day->format('D j') }}</div>
                            @if ($dayShifts->isNotEmpty())
                                <span class="text-[10px] tabular-nums text-zinc-400">{{ $dayShifts->count() }}</span>
                            @endif
                        </div>
                        @foreach ($dayShifts->take(3) as $shift)
                            @php $under = $shift->activeAssignmentsCount() < $shift->required_guards; @endphp
                            <a
                                href="{{ route('schedules.index', ['date' => $shift->starts_at->toDateString()]) }}"
                                @class([
                                    'mt-1 block truncate rounded px-1.5 py-0.5 text-[10px] font-medium transition',
                                    'bg-amber-100 text-amber-900 hover:bg-amber-200 dark:bg-amber-950/60 dark:text-amber-200' => $under,
                                    'bg-accent-600 text-white hover:bg-accent-700' => ! $under,
                                ])
                                title="{{ $shift->starts_at->format('H:i') }} {{ $shift->site?->name }} · {{ $shift->activeAssignmentsCount() }}/{{ $shift->required_guards }}"
                            >
                                <span class="tabular-nums">{{ $shift->starts_at->format('H:i') }}</span> {{ $shift->site?->name }}
                            </a>
                        @endforeach
                        @if ($dayShifts->count() > 3)
                            <a href="{{ route('schedules.index', ['date' => $day->toDateString()]) }}" class="mt-1 block text-[10px] font-medium text-zinc-500 hover:text-accent-700 dark:hover:text-accent-300">
                                +{{ $dayShifts->count() - 3 }} more
                            </a>
                        @endif
                    </div>
                    @php $day->addDay(); @endphp
                @endwhile
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
