<div class="space-y-4">
    @if($statusMessage)
        <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm">{{ $statusMessage }}</div>
    @endif

    <section class="rounded-xl border border-slate-700 bg-slate-800 p-4">
        <h2 class="font-bold">Today's assignments</h2>
        @forelse($assignments as $assignment)
            <label class="mt-2 flex cursor-pointer items-center gap-3 rounded-lg border border-slate-700 p-3">
                <input type="radio" wire:model="activeAssignmentId" value="{{ $assignment->id }}" class="accent-sky-500">
                <div>
                    <div class="font-medium">{{ $assignment->shift?->site?->name }}</div>
                    <div class="text-xs text-slate-400">{{ $assignment->shift?->starts_at?->format('M j, H:i') }}</div>
                </div>
            </label>
        @empty
            <p class="mt-2 text-sm text-slate-400">No assignments found.</p>
        @endforelse
    </section>

    <section class="grid grid-cols-2 gap-3">
        <button wire:click="clockIn" data-geo class="rounded-xl bg-emerald-600 py-4 font-bold">Clock In</button>
        <button wire:click="clockOut" class="rounded-xl bg-amber-600 py-4 font-bold">Clock Out</button>
        <button wire:click="updateLocation" data-geo class="rounded-xl bg-sky-600 py-4 font-bold">Update GPS</button>
        <button wire:click="raiseSos" data-geo class="rounded-xl bg-red-600 py-4 font-bold">SOS</button>
    </section>

    <section class="rounded-xl border border-slate-700 bg-slate-800 p-4">
        <h2 class="mb-3 font-bold">Checkpoint scan</h2>
        <input wire:model="patrolSessionId" type="number" placeholder="Patrol session ID" class="mb-2 w-full rounded-lg border-slate-600 bg-slate-900 px-3 py-2 text-sm">
        <input wire:model="checkpointCode" type="text" placeholder="QR / checkpoint code" class="mb-2 w-full rounded-lg border-slate-600 bg-slate-900 px-3 py-2 text-sm">
        <button wire:click="scanCheckpoint" data-geo class="w-full rounded-lg bg-slate-100 py-2 font-semibold text-slate-900">Scan checkpoint</button>
        <p class="mt-2 text-xs text-slate-400">Use device camera scanner or enter code manually. GPS is captured automatically when available.</p>
    </section>
</div>
