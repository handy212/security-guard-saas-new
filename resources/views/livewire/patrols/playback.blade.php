<div class="space-y-6 p-6">
    <div>
        <h1 class="text-2xl font-bold">Patrol Playback</h1>
        <p class="text-sm text-slate-500">GPS point history for patrol replay.</p>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Operational Workspace</h2>
        <p class="text-sm text-slate-600">Stores patrol playback points for map replay, checkpoint proof, guard route history, and client verification.</p>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Sessions</div><div class="mt-2 text-3xl font-black">{{ $sessions->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">GPS points</div><div class="mt-2 text-3xl font-black">{{ $points->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Replay records</div><div class="mt-2 text-3xl font-black">{{ $points->count() ?? 0 }}</div></div>
    </div>
</div>
