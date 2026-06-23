<div>
    <x-page-header title="Webhook Subscriptions" description="Deliver GuardOps events to external systems." />

    <form wire:submit="create" class="mx-6 mb-6 grid max-w-2xl gap-3 rounded-xl border bg-white p-4 md:grid-cols-3">
        <input wire:model="event" class="rounded-lg border px-3 py-2 text-sm" placeholder="Event code">
        <input wire:model="targetUrl" class="rounded-lg border px-3 py-2 text-sm md:col-span-2" placeholder="https://example.com/webhooks/guardops">
        <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white md:col-span-3">Add webhook</button>
    </form>

    <div class="px-6 pb-6">
        <x-data-table>
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Event</th>
                    <th class="px-4 py-3">URL</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Last delivered</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($subscriptions as $subscription)
                    <tr>
                        <td class="px-4 py-3">{{ $subscription->event }}</td>
                        <td class="px-4 py-3 truncate max-w-xs">{{ $subscription->target_url }}</td>
                        <td class="px-4 py-3">{{ $subscription->is_active ? 'Active' : 'Paused' }}</td>
                        <td class="px-4 py-3">{{ $subscription->last_delivered_at ?? '—' }}</td>
                        <td class="px-4 py-3"><button wire:click="toggle({{ $subscription->id }})" class="text-sm text-sky-700">Toggle</button></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No webhooks configured.</td></tr>
                @endforelse
            </tbody>
        </x-data-table>
    </div>
</div>
