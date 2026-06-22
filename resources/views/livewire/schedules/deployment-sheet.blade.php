<div class="space-y-6 p-6">
    <div>
        <h1 class="text-2xl font-bold">Daily Deployment Sheet</h1>
        <p class="text-sm text-slate-500">Daily guard deployment and export-ready roster.</p>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Operational Workspace</h2>
        <p class="text-sm text-slate-600">Operations can review all assigned guards for the day by site, post, supervisor, and shift time. This is ready for PDF/print export implementation.</p>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Assignments</div><div class="mt-2 text-3xl font-black">{{ $assignments->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Sites covered</div><div class="mt-2 text-3xl font-black">{{ $assignments->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Guards deployed</div><div class="mt-2 text-3xl font-black">{{ $assignments->count() ?? 0 }}</div></div>
    </div>
</div>
