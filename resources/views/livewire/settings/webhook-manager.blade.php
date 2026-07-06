<div>
    <x-page-shell title="Webhook Subscriptions" description="Deliver GuardCore Pro events to external systems.">
        <x-settings-nav />

        <x-form-card title="Add webhook" description="Subscribe to an event and receive POST payloads at your URL.">
            <form wire:submit="create" class="grid gap-4 md:grid-cols-3">
                <x-input wire:model="event" label="Event code" placeholder="incident.created" />
                <x-input wire:model="targetUrl" label="Target URL" placeholder="https://example.com/webhooks/GuardCore Pro" class="md:col-span-2" />
                <div class="md:col-span-3">
                    <x-button type="submit">Add webhook</x-button>
                </div>
            </form>
        </x-form-card>

        <x-data-table title="Active subscriptions">
            <x-table.head>
                <tr>
                    <x-table.th>Event</x-table.th>
                    <x-table.th>URL</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th>Last delivered</x-table.th>
                    <x-table.th align="right">Actions</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($subscriptions as $subscription)
                    <tr class="table-row-hover" wire:key="webhook-{{ $subscription->id }}">
                        <x-table.td><span class="font-medium text-zinc-900">{{ $subscription->event }}</span></x-table.td>
                        <x-table.td muted class="max-w-xs truncate">{{ $subscription->target_url }}</x-table.td>
                        <x-table.td><x-badge :status="$subscription->is_active ? 'active' : 'inactive'" /></x-table.td>
                        <x-table.td muted>{{ $subscription->last_delivered_at?->format('M j, H:i') ?? '—' }}</x-table.td>
                        <x-table.td align="right">
                            <button wire:click="toggle({{ $subscription->id }})" class="table-action">
                                {{ $subscription->is_active ? 'Pause' : 'Activate' }}
                            </button>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="5">
                        <x-empty-state title="No webhooks" description="Add a webhook subscription above." />
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>
    </x-page-shell>
</div>
