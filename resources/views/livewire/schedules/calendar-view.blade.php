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
                <x-filter-select wire:model.live="siteId">
                    <option value="">All sites</option>
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </x-filter-select>
                <x-button type="button" variant="secondary" wire:click="previous" size="sm">Prev</x-button>
                <x-button type="button" variant="secondary" wire:click="next" size="sm">Next</x-button>
            </x-slot:controls>
        </x-page-toolbar>

        {{-- Mobile: agenda list --}}
        <div class="space-y-2 sm:hidden">
            @php $day = $rangeStart->copy(); @endphp
            @while ($day <= $rangeEnd)
                @php $dayShifts = $shifts->filter(fn ($s) => $s->starts_at->isSameDay($day)); @endphp
                @if ($dayShifts->isNotEmpty())
                    <div class="card-surface p-3">
                        <div class="text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">{{ $day->format('D, M j') }}</div>
                        @foreach ($dayShifts as $shift)
                            <a href="{{ route('schedules.index', ['date' => $shift->starts_at->toDateString()]) }}" class="mt-2 flex items-center justify-between text-sm hover:text-accent-700">
                                <span class="font-medium">{{ $shift->starts_at->format('H:i') }} · {{ $shift->site?->name }}</span>
                                <span class="text-xs text-zinc-500">{{ $shift->activeAssignmentsCount() }}/{{ $shift->required_guards }}</span>
                            </a>
                        @endforeach
                    </div>
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
        <div class="hidden grid-cols-7 gap-2 sm:grid">
            @php $day = $rangeStart->copy(); @endphp
            @while ($day <= $rangeEnd)
                @php
                    $dayShifts = $shifts->filter(fn ($s) => $s->starts_at->isSameDay($day));
                    $inMonth = $view === 'week' || $day->month === \Carbon\Carbon::parse($cursorDate)->month;
                @endphp
                <div class="min-h-28 rounded-lg border border-zinc-200 p-2 dark:border-zinc-800 {{ $inMonth ? 'bg-white dark:bg-zinc-900' : 'bg-zinc-50 text-zinc-400 dark:bg-zinc-950' }}">
                    <div class="text-xs font-semibold">{{ $day->format('D j') }}</div>
                    @foreach ($dayShifts->take(3) as $shift)
                        <a href="{{ route('schedules.index', ['date' => $shift->starts_at->toDateString()]) }}" class="mt-1 block truncate rounded bg-accent-600 px-1 py-0.5 text-[10px] text-white hover:bg-accent-700">
                            {{ $shift->starts_at->format('H:i') }} {{ $shift->site?->name }}
                        </a>
                    @endforeach
                    @if ($dayShifts->count() > 3)
                        <div class="mt-1 text-[10px] text-zinc-500">+{{ $dayShifts->count() - 3 }} more</div>
                    @endif
                </div>
                @php $day->addDay(); @endphp
            @endwhile
        </div>
            </x-sub-sidebar-layout>
    </x-page-shell>
</div>
