<div class="p-6 space-y-5">
    <h1 class="text-2xl font-bold">Visitor Log</h1>
    <form wire:submit="checkIn" class="grid gap-3 rounded-xl border bg-white p-4 md:grid-cols-3">
        <select wire:model="form.site_id" class="rounded border p-2" required>
            <option value="">Site</option>
            @foreach($sites as $site)<option value="{{ $site->id }}">{{ $site->name }}</option>@endforeach
        </select>
        <input wire:model="form.visitor_name" class="rounded border p-2" placeholder="Visitor name" required>
        <input wire:model="form.visitor_phone" class="rounded border p-2" placeholder="Phone">
        <input wire:model="form.company" class="rounded border p-2" placeholder="Company">
        <input wire:model="form.purpose" class="rounded border p-2" placeholder="Purpose">
        <input wire:model="form.vehicle_plate" class="rounded border p-2" placeholder="Vehicle plate">
        <button class="rounded bg-slate-900 px-4 py-2 text-white">Check in visitor</button>
    </form>
    <input wire:model.live="search" class="w-full rounded border p-2" placeholder="Search visitors">
    <div class="overflow-auto rounded-xl border bg-white">
        <table class="w-full text-sm">
            <thead><tr class="bg-slate-50 text-left"><th class="p-3">Visitor</th><th>Site</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($items as $item)
                <tr class="border-t">
                    <td class="p-3"><b>{{ $item->visitor_name }}</b><div class="text-slate-500">{{ $item->company }}</div></td>
                    <td>{{ $item->site?->name }}</td>
                    <td>{{ $item->status }}</td>
                    <td>@if(!$item->checked_out_at)<button wire:click="checkOut({{ $item->id }})" class="text-blue-600">Check out</button>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
