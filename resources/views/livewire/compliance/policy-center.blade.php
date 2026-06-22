<div class="p-6 space-y-5">
    <h1 class="text-2xl font-bold">Compliance Policies</h1>
    <div class="grid gap-4 lg:grid-cols-3">
        <form wire:submit="saveEscalation" class="space-y-2 rounded-xl border bg-white p-4">
            <h2 class="font-bold">Escalation Rule</h2>
            <input wire:model="escalationForm.incident_type" class="w-full rounded border p-2" placeholder="Incident type">
            <select wire:model="escalationForm.severity" class="w-full rounded border p-2"><option>low</option><option>medium</option><option>high</option><option>critical</option></select>
            <input wire:model="escalationForm.notify_after_minutes" type="number" class="w-full rounded border p-2" placeholder="Notify after minutes">
            <button class="w-full rounded bg-slate-900 px-3 py-2 text-white">Save rule</button>
        </form>
        <form wire:submit="saveRetention" class="space-y-2 rounded-xl border bg-white p-4">
            <h2 class="font-bold">Retention Policy</h2>
            <input wire:model="retentionForm.record_type" class="w-full rounded border p-2" placeholder="Record type">
            <input wire:model="retentionForm.retention_days" type="number" class="w-full rounded border p-2" placeholder="Retention days">
            <button class="w-full rounded bg-slate-900 px-3 py-2 text-white">Save policy</button>
        </form>
        <form wire:submit="saveSla" class="space-y-2 rounded-xl border bg-white p-4">
            <h2 class="font-bold">SLA Requirement</h2>
            <select wire:model="slaForm.site_id" class="w-full rounded border p-2"><option value="">Site</option>@foreach($sites as $site)<option value="{{ $site->id }}">{{ $site->name }}</option>@endforeach</select>
            <input wire:model="slaForm.metric" class="w-full rounded border p-2" placeholder="Metric">
            <input wire:model="slaForm.target_value" class="w-full rounded border p-2" placeholder="Target value">
            <button class="w-full rounded bg-slate-900 px-3 py-2 text-white">Save SLA</button>
        </form>
    </div>
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border bg-white p-4">@foreach($escalations as $row)<div class="border-t py-2">{{ $row->severity }} · {{ $row->notify_after_minutes }}m</div>@endforeach</div>
        <div class="rounded-xl border bg-white p-4">@foreach($retention as $row)<div class="border-t py-2">{{ $row->record_type }} · {{ $row->retention_days }} days</div>@endforeach</div>
        <div class="rounded-xl border bg-white p-4">@foreach($sla as $row)<div class="border-t py-2">{{ $row->site?->name }} · {{ $row->metric }} = {{ $row->target_value }}</div>@endforeach</div>
    </div>
</div>
