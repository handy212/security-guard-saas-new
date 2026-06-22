<div class="space-y-6 p-6">
    <div>
        <h1 class="text-2xl font-bold">Shift Marketplace</h1>
        <p class="text-sm text-slate-500">Shift swaps, open shift bidding, supervisor approval workflow.</p>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Operational Workspace</h2>
        <p class="text-sm text-slate-600">Guards can request swaps or bid for open shifts; supervisors approve and the roster updates through EnterpriseScheduleService.</p>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Open bids</div><div class="mt-2 text-3xl font-black">{{ $bids->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Swap requests</div><div class="mt-2 text-3xl font-black">{{ $swaps->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Pending approvals</div><div class="mt-2 text-3xl font-black">{{ $swaps->count() ?? 0 }}</div></div>
    </div>
</div>
