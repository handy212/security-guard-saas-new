<div class="space-y-6 p-6">
    <div>
        <h1 class="text-2xl font-bold">Vehicle Patrols</h1>
        <p class="text-sm text-slate-500">Vehicle patrol support with odometer and fuel logs.</p>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Operational Workspace</h2>
        <p class="text-sm text-slate-600">Use this for mobile patrols, vehicle numbers, driver details, odometer readings, and fuel logs.</p>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Vehicle patrols</div><div class="mt-2 text-3xl font-black">{{ $vehiclePatrols->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Active vehicles</div><div class="mt-2 text-3xl font-black">{{ $vehiclePatrols->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Completed patrols</div><div class="mt-2 text-3xl font-black">{{ $vehiclePatrols->count() ?? 0 }}</div></div>
    </div>
</div>
