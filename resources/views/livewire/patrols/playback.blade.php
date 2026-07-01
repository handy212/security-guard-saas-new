<div>
    <x-page-shell title="Patrol Playback" description="Replay guard GPS tracks for completed patrol sessions.">
        <x-slot:actions>
            <select wire:model.live="sessionId" class="form-input text-sm">
                @foreach($sessions as $session)
                    <option value="{{ $session->id }}">#{{ $session->id }} — {{ $session->assignedGuard?->full_name ?? 'Guard' }} ({{ $session->status }})</option>
                @endforeach
            </select>
        </x-slot:actions>

        <div class="grid grid-cols-4 gap-2">
            <x-stat-card compact label="Sessions" :value="$sessions->count()" icon="patrols" />
            <x-stat-card compact label="GPS points" :value="$points->count()" icon="gps" tone="info" />
            <x-stat-card compact label="Track" :value="$points->count() > 1 ? $points->count().' pts' : '—'" icon="plan" />
            <x-stat-card compact label="Status" :value="$points->isNotEmpty() ? 'Ready' : 'Empty'" icon="check" :tone="$points->isNotEmpty() ? 'success' : 'default'" />
        </div>

        @if($points->isNotEmpty())
            <x-map id="playback-map" :lat="$points->first()->latitude" :lng="$points->first()->longitude" :markers="$markers" :polyline="$polyline" height="420px" />
        @else
            <x-empty-state title="No playback data" description="Select a patrol session with GPS points to view the route replay." />
        @endif
    </x-page-shell>
</div>

@script
<script>
    if (window.Echo && @json(auth()->user()?->tenant_id)) {
        // Playback map only — no live channel needed
    }
</script>
@endscript
