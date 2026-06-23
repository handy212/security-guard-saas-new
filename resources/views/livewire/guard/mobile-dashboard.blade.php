<div class="space-y-4" wire:poll.60s="$refresh">
    @if($statusMessage)
        <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm">{{ $statusMessage }}</div>
    @endif

    @error('action')
        <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm">{{ $message }}</div>
    @enderror

    <section class="rounded-xl border border-slate-700 bg-slate-800 p-4">
        <h2 class="font-bold">Today's assignments</h2>
        @forelse($assignments as $assignment)
            <label class="mt-2 flex cursor-pointer items-center gap-3 rounded-lg border border-slate-700 p-3 {{ $activeAssignmentId === $assignment->id ? 'border-sky-500' : '' }}">
                <input type="radio" wire:model.live="activeAssignmentId" value="{{ $assignment->id }}" class="accent-sky-500">
                <div>
                    <div class="font-medium">{{ $assignment->shift?->site?->name }}</div>
                    <div class="text-xs text-slate-400">{{ $assignment->shift?->starts_at?->format('M j, H:i') }} · {{ $assignment->status }}</div>
                </div>
            </label>
        @empty
            <p class="mt-2 text-sm text-slate-400">No assignments found.</p>
        @endforelse
    </section>

    <section class="grid grid-cols-2 gap-3">
        <button type="button"
            onclick="window.guardWithGeo(@this, 'clockIn', 'clock_in', (c, w) => ({ shift_assignment_id: w.activeAssignmentId, latitude: c.lat, longitude: c.lng }))"
            class="rounded-xl bg-emerald-600 py-4 font-bold">Clock In</button>
        <button type="button"
            onclick="window.guardWithGeo(@this, 'clockOut', 'clock_out', (c, w) => ({ attendance_log_id: w.activeAttendanceId, latitude: c.lat, longitude: c.lng }))"
            class="rounded-xl bg-amber-600 py-4 font-bold" @disabled(! $activeAttendanceId)>Clock Out</button>
        <button type="button"
            onclick="window.guardWithGeo(@this, 'updateLocation', 'location', (c) => ({ latitude: c.lat, longitude: c.lng }))"
            class="rounded-xl bg-sky-600 py-4 font-bold">Update GPS</button>
        <button type="button"
            onclick="window.guardWithGeo(@this, 'raiseSos', 'sos', (c) => ({ latitude: c.lat, longitude: c.lng, message: 'SOS (offline queued)' }))"
            class="rounded-xl bg-red-600 py-4 font-bold">SOS</button>
    </section>

    <section class="rounded-xl border border-slate-700 bg-slate-800 p-4">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-bold">Patrol</h2>
            @if($activeAttendanceId)
                <span class="text-xs text-emerald-400">On shift</span>
            @endif
        </div>

        @if($activePatrols->isNotEmpty())
            <div class="mb-3 space-y-2">
                <div class="text-xs uppercase text-slate-400">Active sessions</div>
                @foreach($activePatrols as $patrol)
                    <button type="button" wire:click="$set('patrolSessionId', {{ $patrol->id }})"
                        class="w-full rounded-lg border px-3 py-2 text-left text-sm {{ $patrolSessionId === $patrol->id ? 'border-sky-500 bg-sky-500/10' : 'border-slate-600' }}">
                        #{{ $patrol->id }} — {{ $patrol->route?->name }}
                    </button>
                @endforeach
            </div>
        @endif

        @if($patrolRoutes->isNotEmpty())
            <div class="mb-3">
                <div class="mb-1 text-xs uppercase text-slate-400">Start new patrol</div>
                <div class="flex flex-wrap gap-2">
                    @foreach($patrolRoutes as $route)
                        <button type="button" wire:click="startPatrol({{ $route->id }})"
                            class="rounded-lg border border-slate-600 px-3 py-1 text-xs hover:border-sky-500">
                            {{ $route->name }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    <section class="rounded-xl border border-slate-700 bg-slate-800 p-4">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-bold">Checkpoint scan</h2>
            <button type="button" wire:click="toggleScanner" class="rounded-lg bg-sky-600 px-3 py-1 text-xs font-semibold">
                {{ $showScanner ? 'Close camera' : 'Open camera' }}
            </button>
        </div>

        @if($showScanner)
            <div id="qr-reader" class="mb-3 overflow-hidden rounded-lg border border-slate-600" wire:ignore></div>
            @script
            <script>
                $wire.$watch('showScanner', async (show) => {
                    if (show) {
                        await window.startQrScanner('qr-reader', (code) => {
                            $wire.dispatch('qr-scanned', { code });
                            window.stopQrScanner();
                        });
                    } else {
                        await window.stopQrScanner();
                    }
                });
                if ($wire.showScanner) {
                    window.startQrScanner('qr-reader', (code) => {
                        $wire.dispatch('qr-scanned', { code });
                        window.stopQrScanner();
                    });
                }
            </script>
            @endscript
        @endif

        <select wire:model="patrolSessionId" class="mb-2 w-full rounded-lg border-slate-600 bg-slate-900 px-3 py-2 text-sm">
            <option value="">Select patrol session</option>
            @foreach($activePatrols as $patrol)
                <option value="{{ $patrol->id }}">#{{ $patrol->id }} — {{ $patrol->route?->name }}</option>
            @endforeach
        </select>
        <input wire:model="checkpointCode" type="text" placeholder="QR / checkpoint code" class="mb-2 w-full rounded-lg border-slate-600 bg-slate-900 px-3 py-2 text-sm">
        <button type="button"
            onclick="window.guardWithGeo(@this, 'scanCheckpoint', 'checkpoint_scan', (c, w) => ({ patrol_session_id: w.patrolSessionId, checkpoint_code: w.checkpointCode, latitude: c.lat, longitude: c.lng }))"
            class="w-full rounded-lg bg-slate-100 py-2 font-semibold text-slate-900">Submit scan</button>
    </section>

    <p class="text-center text-[10px] text-slate-500">Install this app from your browser menu for fullscreen field use.</p>
</div>
