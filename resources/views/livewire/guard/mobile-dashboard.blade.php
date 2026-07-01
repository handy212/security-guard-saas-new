<div class="space-y-4" wire:poll.60s="$refresh">
    @if($statusMessage)
        <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm" role="status">{{ $statusMessage }}</div>
    @endif

    @error('action')
        <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm" role="alert">{{ $message }}</div>
    @enderror

    <section id="assignments" class="scroll-mt-20 rounded-lg border border-zinc-700 bg-zinc-800 p-4">
        <h2 class="font-bold">Today's assignments</h2>
        @forelse($assignments as $assignment)
            <label class="mt-2 flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-700 p-3 {{ $activeAssignmentId === $assignment->id ? 'border-sky-500' : '' }}">
                <input type="radio" wire:model.live="activeAssignmentId" value="{{ $assignment->id }}" class="accent-sky-500">
                <div>
                    <div class="font-medium">{{ $assignment->shift?->site?->name }}</div>
                    <div class="text-xs text-zinc-400">{{ $assignment->shift?->starts_at?->format('M j, H:i') }} · {{ $assignment->status }}</div>
                </div>
            </label>
        @empty
            <p class="mt-2 text-sm text-zinc-400">No assignments found.</p>
        @endforelse
    </section>

    <section class="grid grid-cols-2 gap-3">
        <button type="button"
            onclick="window.guardWithGeo(@this, 'clockIn', 'clock_in', (c, w) => ({ shift_assignment_id: w.activeAssignmentId, latitude: c.lat, longitude: c.lng }))"
            wire:loading.attr="disabled"
            wire:target="clockIn"
            class="rounded-lg bg-emerald-600 py-4 text-base font-bold disabled:opacity-60">
            <span wire:loading.remove wire:target="clockIn">Clock In</span>
            <span wire:loading wire:target="clockIn">Working…</span>
        </button>
        <button type="button"
            onclick="window.guardWithGeo(@this, 'clockOut', 'clock_out', (c, w) => ({ attendance_log_id: w.activeAttendanceId, latitude: c.lat, longitude: c.lng }))"
            wire:loading.attr="disabled"
            wire:target="clockOut"
            class="rounded-lg bg-amber-600 py-4 text-base font-bold disabled:opacity-60"
            @disabled(! $activeAttendanceId)>
            <span wire:loading.remove wire:target="clockOut">Clock Out</span>
            <span wire:loading wire:target="clockOut">Working…</span>
        </button>
        <button type="button"
            onclick="window.guardWithGeo(@this, 'updateLocation', 'location', (c) => ({ latitude: c.lat, longitude: c.lng }))"
            wire:loading.attr="disabled"
            wire:target="updateLocation"
            class="col-span-2 rounded-lg bg-sky-600 py-3 text-base font-bold disabled:opacity-60">
            <span wire:loading.remove wire:target="updateLocation">Update GPS location</span>
            <span wire:loading wire:target="updateLocation">Updating…</span>
        </button>
    </section>

    <section
        id="sos"
        class="scroll-mt-20 rounded-xl border-2 border-red-500/60 bg-red-950/40 p-4"
        x-data="{ armed: false }"
    >
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-red-100">Emergency SOS</h2>
                <p class="mt-1 text-xs text-red-200/80">Alerts dispatch immediately with your GPS position.</p>
            </div>
            <span class="rounded-full bg-red-500/20 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-red-200">Critical</span>
        </div>

        <template x-if="! armed">
            <button
                type="button"
                @click="armed = true"
                class="mt-4 w-full rounded-lg bg-red-600 py-4 text-lg font-bold text-white shadow-lg shadow-red-900/40"
            >
                Raise SOS alert
            </button>
        </template>

        <div x-show="armed" x-cloak class="mt-4 space-y-3">
            <p class="text-sm font-medium text-red-100">Confirm emergency — dispatch will be notified.</p>
            <div class="grid grid-cols-2 gap-3">
                <button type="button" @click="armed = false" class="rounded-lg border border-red-400/40 py-3 font-semibold text-red-100">Cancel</button>
                <button type="button"
                    @click="armed = false; window.guardWithGeo(@this, 'raiseSos', 'sos', (c) => ({ latitude: c.lat, longitude: c.lng, message: 'SOS (offline queued)' }))"
                    wire:loading.attr="disabled"
                    wire:target="raiseSos"
                    class="rounded-lg bg-red-600 py-3 font-bold text-white disabled:opacity-60">
                    <span wire:loading.remove wire:target="raiseSos">Confirm SOS</span>
                    <span wire:loading wire:target="raiseSos">Sending…</span>
                </button>
            </div>
        </div>
    </section>

    <section id="patrol" class="scroll-mt-20 rounded-lg border border-zinc-700 bg-zinc-800 p-4">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-bold">Patrol</h2>
            @if($activeAttendanceId)
                <span class="text-xs text-emerald-400">On shift</span>
            @endif
        </div>

        @if($activePatrols->isNotEmpty())
            <div class="mb-3 space-y-2">
                <div class="text-xs uppercase text-zinc-400">Active sessions</div>
                @foreach($activePatrols as $patrol)
                    <button type="button" wire:click="$set('patrolSessionId', {{ $patrol->id }})"
                        class="w-full rounded-lg border px-3 py-2 text-left text-sm {{ $patrolSessionId === $patrol->id ? 'border-sky-500 bg-sky-500/10' : 'border-zinc-600' }}">
                        #{{ $patrol->id }} — {{ $patrol->route?->name }}
                    </button>
                @endforeach
            </div>
        @endif

        @if($patrolRoutes->isNotEmpty())
            <div class="mb-3">
                <div class="mb-1 text-xs uppercase text-zinc-400">Start new patrol</div>
                <div class="flex flex-wrap gap-2">
                    @foreach($patrolRoutes as $route)
                        <button type="button" wire:click="startPatrol({{ $route->id }})"
                            wire:loading.attr="disabled"
                            wire:target="startPatrol"
                            class="rounded-lg border border-zinc-600 px-3 py-2 text-xs hover:border-sky-500 disabled:opacity-60">
                            {{ $route->name }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    <section id="scan" class="scroll-mt-20 rounded-lg border border-zinc-700 bg-zinc-800 p-4">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-bold">Checkpoint scan</h2>
            <button type="button" wire:click="toggleScanner" class="rounded-lg bg-sky-600 px-3 py-2 text-xs font-semibold">
                {{ $showScanner ? 'Close camera' : 'Open camera' }}
            </button>
        </div>

        @if($showScanner)
            <div id="qr-reader" class="mb-3 overflow-hidden rounded-lg border border-zinc-600" wire:ignore></div>
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

        <select wire:model="patrolSessionId" class="mb-2 w-full rounded-lg border-zinc-600 bg-zinc-900 px-3 py-2 text-sm">
            <option value="">Select patrol session</option>
            @foreach($activePatrols as $patrol)
                <option value="{{ $patrol->id }}">#{{ $patrol->id }} — {{ $patrol->route?->name }}</option>
            @endforeach
        </select>
        <input wire:model="checkpointCode" type="text" placeholder="QR / checkpoint code" class="mb-2 w-full rounded-lg border-zinc-600 bg-zinc-900 px-3 py-2 text-sm">
        <button type="button"
            onclick="window.guardWithGeo(@this, 'scanCheckpoint', 'checkpoint_scan', (c, w) => ({ patrol_session_id: w.patrolSessionId, checkpoint_code: w.checkpointCode, latitude: c.lat, longitude: c.lng }))"
            wire:loading.attr="disabled"
            wire:target="scanCheckpoint"
            class="w-full rounded-lg bg-zinc-100 py-3 font-semibold text-zinc-900 disabled:opacity-60">
            <span wire:loading.remove wire:target="scanCheckpoint">Submit scan</span>
            <span wire:loading wire:target="scanCheckpoint">Submitting…</span>
        </button>
    </section>

    <p class="text-center text-[10px] text-zinc-500">Install this app from your browser menu for fullscreen field use.</p>
</div>
