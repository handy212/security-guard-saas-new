<div class="space-y-6 p-6">
    <div>
        <h1 class="text-2xl font-bold">SaaS / Tenant Management</h1>
        <p class="text-sm text-slate-500">Manage branches, subscription plans, tenant users, tenant settings, and billing limits.</p>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Operational Workspace</h2>
        <p class="text-sm text-slate-600">This screen is the SaaS control center for tenant limits, branch operations, plan enforcement, and company settings.</p>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Branches</div><div class="mt-2 text-3xl font-black">{{ $branches->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Plans</div><div class="mt-2 text-3xl font-black">{{ $plans->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Billing limits</div><div class="mt-2 text-3xl font-black">{{ $limits->count() ?? 0 }}</div></div>
    </div>
</div>
