<div class="space-y-6 p-6">
    <div>
        <h1 class="text-2xl font-bold">Compliance Policy Center</h1>
        <p class="text-sm text-slate-500">SLA, escalation, retention, audit and compliance controls.</p>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Operational Workspace</h2>
        <p class="text-sm text-slate-600">Configure incident escalation, data retention, and site SLA policies across the tenant.</p>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Escalation rules</div><div class="mt-2 text-3xl font-black">{{ $escalations->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Retention policies</div><div class="mt-2 text-3xl font-black">{{ $retention->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">SLA rules</div><div class="mt-2 text-3xl font-black">{{ $sla->count() ?? 0 }}</div></div>
    </div>
</div>
