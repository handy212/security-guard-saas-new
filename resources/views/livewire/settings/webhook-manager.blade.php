<div>
    <x-page-shell
        title="Webhook Subscriptions"
        description="Deliver GuardCore Pro events to external systems."
        :breadcrumbs="[
            ['label' => 'Settings', 'href' => route('settings.index')],
            ['label' => 'Webhooks'],
        ]"
    >
        <x-slot:actions>
            <x-button wire:click="openForm">Add webhook</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <x-flash-status />

            <div class="stat-grid">
                <x-stat-card compact label="Subscriptions" :value="$stats['total']" icon="plan" />
                <x-stat-card compact label="Active" :value="$stats['active']" icon="check" :tone="$stats['active'] ? 'success' : 'default'" />
            </div>

            <x-section-card title="Active subscriptions" :description="$stats['total'] ? 'POST payloads fire when matching events occur' : 'No webhooks configured'" flush>
                <x-data-table>
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
                                <x-table.td mono>{{ str_replace('.', ' › ', $subscription->event) }}</x-table.td>
                                <x-table.td muted class="max-w-xs truncate font-mono text-xs">{{ $subscription->target_url }}</x-table.td>
                                <x-table.td><x-badge :status="$subscription->is_active ? 'active' : 'inactive'" /></x-table.td>
                                <x-table.td muted class="tabular-nums">{{ $subscription->last_delivered_at?->format('M j, H:i') ?? '—' }}</x-table.td>
                                <x-table.td align="right">
                                    <div class="table-inline-actions justify-end">
                                        <button type="button" wire:click="edit({{ $subscription->id }})" class="table-action">Edit</button>
                                        <button type="button" wire:click="toggle({{ $subscription->id }})" class="table-action">
                                            {{ $subscription->is_active ? 'Pause' : 'Activate' }}
                                        </button>
                                        <button type="button" wire:click="delete({{ $subscription->id }})" wire:confirm="Delete this webhook?" class="table-action-danger">Delete</button>
                                    </div>
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="5">
                                <x-empty-state compact title="No webhooks" description="Add a subscription to deliver events to an external URL.">
                                    <x-slot:actions>
                                        <x-button size="sm" wire:click="openForm">Add webhook</x-button>
                                    </x-slot:actions>
                                </x-empty-state>
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
            </x-section-card>
        </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($showForm)
        <x-drawer
            :title="$editingId ? 'Edit webhook' : 'Add webhook'"
            description="Subscribe to an event and receive POST payloads at your URL."
            width="md"
            close-method="closeDrawer"
        >
            <x-drawer-form wire:submit.prevent="create" :submit-label="$editingId ? 'Save changes' : 'Add webhook'" close-method="closeDrawer" target="create">
                <x-form-section title="Subscription">
                    <x-input wire:model="event" label="Event code *" placeholder="incident.created" class="sm:col-span-2" />
                    <x-input wire:model="targetUrl" label="Target URL *" placeholder="https://example.com/webhooks" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
