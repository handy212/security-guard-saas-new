<div class="p-6 space-y-5">
    <h1 class="text-2xl font-bold">Equipment Assets</h1>
    <form wire:submit="save" class="grid gap-3 rounded-xl border bg-white p-4 md:grid-cols-3">
        <input wire:model="form.name" class="rounded border p-2" placeholder="Name" required>
        <input wire:model="form.asset_tag" class="rounded border p-2" placeholder="Asset tag">
        <input wire:model="form.category" class="rounded border p-2" placeholder="Category">
        <input wire:model="form.serial_number" class="rounded border p-2" placeholder="Serial number">
        <select wire:model="form.condition" class="rounded border p-2"><option>good</option><option>fair</option><option>poor</option></select>
        <select wire:model="form.status" class="rounded border p-2"><option>available</option><option>issued</option><option>retired</option></select>
        <button class="rounded bg-slate-900 px-4 py-2 text-white md:col-span-3">{{ $editingId ? 'Update' : 'Create' }} Asset</button>
    </form>
    <input wire:model.live="search" class="w-full rounded border p-2" placeholder="Search equipment">
    <div class="overflow-auto rounded-xl border bg-white">
        <table class="w-full text-sm">
            <thead><tr class="bg-slate-50 text-left"><th class="p-3">Asset</th><th>Tag</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($items as $item)
                <tr class="border-t">
                    <td class="p-3"><b>{{ $item->name }}</b><div class="text-slate-500">{{ $item->category }}</div></td>
                    <td>{{ $item->asset_tag }}</td>
                    <td>{{ $item->status }}</td>
                    <td class="space-x-2 p-3">
                        <button wire:click="edit({{ $item->id }})" class="text-blue-600">Edit</button>
                        <button wire:click="delete({{ $item->id }})" class="text-red-600">Delete</button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
