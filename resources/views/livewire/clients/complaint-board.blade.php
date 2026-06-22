<div class="p-6 space-y-5">
    <h1 class="text-2xl font-bold">Client Complaints</h1>
    <form wire:submit="save" class="grid gap-3 rounded-xl border bg-white p-4 md:grid-cols-2">
        <select wire:model="form.client_account_id" class="rounded border p-2" required>
            <option value="">Client</option>
            @foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach
        </select>
        <select wire:model="form.site_id" class="rounded border p-2">
            <option value="">Site (optional)</option>
            @foreach($sites as $site)<option value="{{ $site->id }}">{{ $site->name }}</option>@endforeach
        </select>
        <input wire:model="form.subject" class="rounded border p-2 md:col-span-2" placeholder="Subject" required>
        <textarea wire:model="form.description" class="rounded border p-2 md:col-span-2" placeholder="Description" required></textarea>
        <select wire:model="form.priority" class="rounded border p-2"><option>low</option><option>normal</option><option>high</option></select>
        <button class="rounded bg-slate-900 px-4 py-2 text-white">Log complaint</button>
    </form>
    @foreach($complaints as $complaint)
        <div class="rounded-xl border bg-white p-4 flex justify-between">
            <div>
                <b>{{ $complaint->subject }}</b>
                <div class="text-sm text-slate-500">{{ $complaint->clientAccount?->name }} · {{ $complaint->site?->name }} · {{ $complaint->priority }} · {{ $complaint->status }}</div>
                <p class="mt-2 text-sm">{{ $complaint->description }}</p>
            </div>
            @if($complaint->status !== 'resolved')
                <button wire:click="resolve({{ $complaint->id }})" class="h-fit rounded bg-green-700 px-3 py-1 text-white">Resolve</button>
            @endif
        </div>
    @endforeach
</div>
