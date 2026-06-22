<div class="space-y-6 p-6">
    <div>
        <h1 class="text-2xl font-bold">Site Compliance</h1>
        <p class="text-sm text-slate-500">Emergency contacts, site documents, SLA requirements and post compliance.</p>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Operational Workspace</h2>
        <p class="text-sm text-slate-600">This completes enterprise site management with documents, contacts, geofence/SLA support and client-visible records.</p>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Emergency contacts</div><div class="mt-2 text-3xl font-black">{{ $contacts->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Documents</div><div class="mt-2 text-3xl font-black">{{ $documents->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">SLA requirements</div><div class="mt-2 text-3xl font-black">{{ $sla->count() ?? 0 }}</div></div>
    </div>
</div>
