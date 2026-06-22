<div class="space-y-6 p-6">
    <div>
        <h1 class="text-2xl font-bold">Payroll & Accounting Exports</h1>
        <p class="text-sm text-slate-500">Timesheets, overtime, allowances, deductions, and CSV accounting exports.</p>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Operational Workspace</h2>
        <p class="text-sm text-slate-600">Payroll can be generated from approved attendance and shifts; invoices can be exported later to QuickBooks/Xero/CSV.</p>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Timesheets</div><div class="mt-2 text-3xl font-black">{{ $timesheets->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Exports</div><div class="mt-2 text-3xl font-black">{{ $exports->count() ?? 0 }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Payroll runs</div><div class="mt-2 text-3xl font-black">{{ $timesheets->count() ?? 0 }}</div></div>
    </div>
</div>
