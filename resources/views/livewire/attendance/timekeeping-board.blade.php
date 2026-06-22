<div class="space-y-6 p-6">
    <div>
        <h1 class="text-2xl font-bold">Attendance & Timekeeping</h1>
        <p class="text-sm text-slate-500">Clock in/out, GPS validation, late/no-show/early-leave and break tracking.</p>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Operational Workspace</h2>
        <p class="text-sm text-slate-600">This board centralizes attendance logs and break logs for payroll-ready timekeeping.</p>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Attendance logs</div><div class="mt-2 text-3xl font-black">{{ $logs->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Break logs</div><div class="mt-2 text-3xl font-black">{{ $breaks->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Exceptions</div><div class="mt-2 text-3xl font-black">{{ $logs->count() ?? 0 }}</div></div>
    </div>
</div>
