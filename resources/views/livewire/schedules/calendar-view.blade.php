<div>
    <x-page-shell title="Schedule Calendar" description="Monthly and weekly shift planning view.">
        <x-slot:actions>
            <a href="{{ route('schedules.index') }}" class="btn-secondary text-sm">Day list</a>
        </x-slot:actions>

        <div class="grid grid-cols-4 gap-2">
            <x-stat-card compact label="In range" :value="$stats['total']" icon="schedules" />
            <x-stat-card compact label="Open" :value="$stats['open']" icon="pause" :tone="$stats['open'] > 0 ? 'warning' : 'default'" />
            <x-stat-card compact label="Posts" :value="$stats['posts']" icon="plan" tone="info" />
            <x-stat-card compact label="View" :value="ucfirst($view)" icon="check" />
        </div>

        <x-page-toolbar>
            <x-slot:tabs>
                <x-segment-control model="view" :active="$view" :options="['month' => 'Month', 'week' => 'Week']" />
            </x-slot:tabs>
            <x-slot:controls>
                <select wire:model.live="siteId" class="form-input w-auto min-w-[8.5rem] text-sm">
                    <option value="">All sites</option>
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </select>
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
                    <div class="rounded-lg border border-zinc-200 bg-white p-3">
                        <div class="text-xs font-semibold uppercase text-zinc-500">{{ $day->format('D, M j') }}</div>
                        @foreach ($dayShifts as $shift)
                            <div class="mt-2 flex items-center justify-between text-sm">
                                <span class="font-medium">{{ $shift->starts_at->format('H:i') }} · {{ $shift->site?->name }}</span>
                                <span class="text-xs text-zinc-500">{{ $shift->assignments->count() }}/{{ $shift->required_guards }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                @php $day->addDay(); @endphp
            @endwhile
            @if ($shifts->isEmpty())
                <x-empty-state title="No shifts in range" />
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
                <div class="min-h-28 rounded-lg border p-2 {{ $inMonth ? 'bg-white' : 'bg-zinc-100 text-zinc-400' }}">
                    <div class="text-xs font-semibold">{{ $day->format('D j') }}</div>
                    @foreach ($dayShifts->take(3) as $shift)
                        <div class="mt-1 truncate rounded bg-zinc-900 px-1 py-0.5 text-[10px] text-white">
                            {{ $shift->starts_at->format('H:i') }} {{ $shift->site?->name }}
                        </div>
                    @endforeach
                    @if ($dayShifts->count() > 3)
                        <div class="mt-1 text-[10px] text-zinc-500">+{{ $dayShifts->count() - 3 }} more</div>
                    @endif
                </div>
                @php $day->addDay(); @endphp
            @endwhile
        </div>
    </x-page-shell>
</div>
