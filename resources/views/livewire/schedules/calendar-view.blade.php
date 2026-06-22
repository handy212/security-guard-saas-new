<div class="space-y-6 p-6">
    <div>
        <h1 class="text-2xl font-bold">Scheduling Calendar</h1>
        <p class="text-sm text-slate-500">Monthly and weekly schedule view foundation.</p>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Operational Workspace</h2>
        <p class="text-sm text-slate-600">Use this page to render a calendar grid for assigned shifts, open shifts, site posts, and conflict warnings.</p>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">This month shifts</div><div class="mt-2 text-3xl font-black">{{ $shifts->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Open shifts</div><div class="mt-2 text-3xl font-black">{{ $shifts->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Scheduled posts</div><div class="mt-2 text-3xl font-black">{{ $shifts->count() ?? 0 }}</div></div>
    </div>
</div>
