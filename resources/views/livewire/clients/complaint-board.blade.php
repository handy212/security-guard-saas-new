<div class="space-y-6 p-6">
    <div>
        <h1 class="text-2xl font-bold">Client Complaints</h1>
        <p class="text-sm text-slate-500">Client complaint and service issue workflow.</p>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Operational Workspace</h2>
        <p class="text-sm text-slate-600">Clients can raise complaints; operations can assign, resolve, and track response SLA.</p>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Complaints</div><div class="mt-2 text-3xl font-black">{{ $complaints->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Open cases</div><div class="mt-2 text-3xl font-black">{{ $complaints->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Resolved cases</div><div class="mt-2 text-3xl font-black">{{ $complaints->count() ?? 0 }}</div></div>
    </div>
</div>
