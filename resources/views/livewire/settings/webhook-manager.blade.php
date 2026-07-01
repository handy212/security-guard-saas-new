<div>
    <x-page-shell title="Webhook Subscriptions" description="Deliver GuardOps events to external systems.">
        <x-settings-nav />

        <x-form-card title="Add webhook" description="Subscribe to an event and receive POST payloads at your URL.">
            <form wire:submit="create" class="grid gap-4 md:grid-cols-3">
                <x-input wire:model="event" label="Event code" placeholder="incident.created" />
                <x-input wire:model="targetUrl" label="Target URL" placeholder="https://example.com/webhooks/guardops" class="md:col-span-2" />
                <div class="md:col-span-3">
                    <x-button type="submit">Add webhook</x-button>
                </div>
            </form>
        </x-form-card>

        <x-data-table title="Active subscriptions">
            <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                <tr>
                    <th class="px-3 py-2">Event</th>
                    <th class="px-3 py-2">URL</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2">Last delivered</th>
                    <th class="px-3 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $subscription)
                    <tr class="table-row-hover">
                        <td class="px-3 py-2 font-medium text-zinc-900">{{ $subscription->event }}</td>
                        <td class="px-3 py-2 max-w-xs truncate text-zinc-600">{{ $subscription->target_url }}</td>
                        <td class="px-3 py-2">
                            <x-badge :status="$subscription->is_active ? 'active' : 'inactive'" />
                        </td>
                        <td class="px-3 py-2 text-zinc-600">{{ $subscription->last_delivered_at?->format('M j, H:i') ?? '—' }}</td>
                        <td class="px-3 py-2 text-right">
                            <button wire:click="toggle({{ $subscription->id }})" class="btn-link">
                                {{ $subscription->is_active ? 'Pause' : 'Activate' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-8"><x-empty-state title="No webhooks" description="Add a webhook subscription above." /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>
    </x-page-shell>
</div>
