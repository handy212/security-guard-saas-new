<div class="space-y-6 p-6">
    <div>
        <h1 class="text-2xl font-bold">Guard HR Records</h1>
        <p class="text-sm text-slate-500">Skills, training records, license expiry and disciplinary history.</p>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Operational Workspace</h2>
        <p class="text-sm text-slate-600">This completes the HR side of guard management beyond basic guard profiles.</p>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Skills</div><div class="mt-2 text-3xl font-black">{{ $skills->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Training</div><div class="mt-2 text-3xl font-black">{{ $training->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Disciplinary</div><div class="mt-2 text-3xl font-black">{{ $disciplinary->count() ?? 0 }}</div></div>
    </div>
</div>
