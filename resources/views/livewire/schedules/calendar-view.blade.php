<div>
    <x-page-header title="Schedule Calendar" description="Monthly and weekly shift planning view.">
        <x-slot:actions>
            <select wire:model.live="view" class="rounded-lg border px-3 py-2 text-sm">
                <option value="month">Month</option>
                <option value="week">Week</option>
            </select>
            <select wire:model.live="siteId" class="rounded-lg border px-3 py-2 text-sm">
                <option value="">All sites</option>
                @foreach($sites as $site)
                    <option value="{{ $site->id }}">{{ $site->name }}</option>
                @endforeach
            </select>
            <button wire:click="previous" class="rounded-lg border px-3 py-2 text-sm">Prev</button>
            <button wire:click="next" class="rounded-lg border px-3 py-2 text-sm">Next</button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 p-6 md:grid-cols-3">
        <x-stat-card label="Shifts in range" :value="$stats['total']" />
        <x-stat-card label="Open shifts" :value="$stats['open']" tone="warning" />
        <x-stat-card label="Scheduled posts" :value="$stats['posts']" tone="info" />
    </div>

    <div class="grid grid-cols-7 gap-2 px-6 pb-6">
        @php $day = $rangeStart->copy(); @endphp
        @while($day <= $rangeEnd)
            @php
                $dayShifts = $shifts->filter(fn ($s) => $s->starts_at->isSameDay($day));
                $inMonth = $view === 'week' || $day->month === \Carbon\Carbon::parse($cursorDate)->month;
            @endphp
            <div class="min-h-28 rounded-lg border p-2 {{ $inMonth ? 'bg-white' : 'bg-slate-100 text-slate-400' }}">
                <div class="text-xs font-semibold">{{ $day->format('D j') }}</div>
                @foreach($dayShifts->take(3) as $shift)
                    <div class="mt-1 truncate rounded bg-slate-900 px-1 py-0.5 text-[10px] text-white">
                        {{ $shift->starts_at->format('H:i') }} {{ $shift->site?->name }}
                    </div>
                @endforeach
                @if($dayShifts->count() > 3)
                    <div class="mt-1 text-[10px] text-slate-500">+{{ $dayShifts->count() - 3 }} more</div>
                @endif
            </div>
            @php $day->addDay(); @endphp
        @endwhile
    </div>
</div>
