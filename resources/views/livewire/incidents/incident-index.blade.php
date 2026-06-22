<div class="p-6 space-y-5"><h1 class="text-2xl font-bold">Incident Reports</h1>
<form wire:submit="save" class="grid gap-3 rounded-xl border bg-white p-4 md:grid-cols-3">
    <select wire:model="form.site_id" class="rounded border p-2"><option value="">Site</option>@foreach($sites as $site)<option value="{{ $site->id }}">{{ $site->name }}</option>@endforeach</select>
    <input wire:model="form.title" class="rounded border p-2" placeholder="Title">
    <input wire:model="form.type" class="rounded border p-2" placeholder="Type">
    <select wire:model="form.severity" class="rounded border p-2"><option>low</option><option>medium</option><option>high</option><option>critical</option></select>
    <textarea wire:model="form.description" class="rounded border p-2 md:col-span-2" placeholder="Description"></textarea>
    <button class="rounded bg-slate-900 px-4 py-2 text-white">Submit Incident</button>
</form>
<form wire:submit="uploadMedia" class="grid gap-3 rounded-xl border bg-white p-4 md:grid-cols-3">
    <select wire:model="uploadIncidentId" class="rounded border p-2"><option value="">Incident for media</option>@foreach($incidents as $incident)<option value="{{ $incident->id }}">#{{ $incident->id }} {{ $incident->title }}</option>@endforeach</select>
    <input wire:model="mediaFile" type="file" class="rounded border p-2">
    <button class="rounded bg-blue-700 px-4 py-2 text-white">Upload media</button>
</form>
<input wire:model.live="search" class="w-full rounded border p-2" placeholder="Search incidents">
<div class="overflow-auto rounded-xl border bg-white"><table class="w-full text-sm"><thead><tr class="bg-slate-50 text-left"><th class="p-3">Incident</th><th>Site</th><th>Severity</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($incidents as $incident)<tr class="border-t"><td class="p-3"><b>{{ $incident->title }}</b><div class="text-slate-500">{{ $incident->type }}</div></td><td>{{ $incident->site?->name }}</td><td>{{ $incident->severity }}</td><td>{{ $incident->status }}</td><td class="space-x-2"><button wire:click="approve({{ $incident->id }})" class="text-blue-600">Approve</button><button wire:click="close({{ $incident->id }})" class="text-green-700">Close</button><button wire:click="exportPdf({{ $incident->id }})" class="text-slate-700">PDF</button></td></tr>@endforeach
</tbody></table></div>{{ $incidents->links() }}</div>
