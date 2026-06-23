<div>
    <x-page-header title="Patrol Playback" description="Replay guard GPS tracks for completed patrol sessions.">
        <x-slot:actions>
            <select wire:model.live="sessionId" class="rounded-lg border px-3 py-2 text-sm">
                @foreach($sessions as $session)
                    <option value="{{ $session->id }}">#{{ $session->id }} — {{ $session->assignedGuard?->full_name ?? 'Guard' }} ({{ $session->status }})</option>
                @endforeach
            </select>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 p-6 md:grid-cols-3">
        <x-stat-card label="Sessions" :value="$sessions->count()" />
        <x-stat-card label="GPS points" :value="$points->count()" tone="info" />
        <x-stat-card label="Track length" :value="$points->count() > 1 ? $points->count().' pts' : '—'" />
    </div>

    <div class="px-6 pb-6">
        @if($points->isNotEmpty())
            <x-map id="playback-map" :lat="$points->first()->latitude" :lng="$points->first()->longitude" :markers="$markers" :polyline="$polyline" height="420px" />
        @else
            <x-empty-state title="No playback data" description="Select a patrol session with GPS points to view the route replay." />
        @endif
    </div>
</div>
