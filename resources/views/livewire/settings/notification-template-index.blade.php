<div>
    <x-page-shell
        title="Notification templates"
        description="Customize email and in-app notification copy for operational events."
        :breadcrumbs="[
            ['label' => 'Settings', 'href' => route('settings.index')],
            ['label' => 'Notifications'],
        ]"
    >
        <x-slot:actions>
            <x-button wire:click="openForm">Add template</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <x-flash-status />

            <div class="stat-grid">
                <x-stat-card compact label="Templates" :value="$stats['total']" icon="plan" />
                <x-stat-card compact label="Active" :value="$stats['active']" icon="check" tone="success" />
                <x-stat-card compact label="Email" :value="$stats['mail']" icon="billing" tone="info" />
            </div>

            <x-section-card title="Templates" :description="$stats['total'] ? 'Event codes map to outbound notifications' : 'No templates yet'" flush>
                <x-data-table>
                    <x-table.head>
                        <tr>
                            <x-table.th>Code</x-table.th>
                            <x-table.th>Channel</x-table.th>
                            <x-table.th responsive="md">Subject</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th align="right">Actions</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @forelse ($templates as $template)
                            <tr class="table-row-hover" wire:key="tpl-{{ $template->id }}">
                                <x-table.td class="font-mono text-sm text-zinc-900 dark:text-zinc-100">{{ $template->code }}</x-table.td>
                                <x-table.td>
                                    <span class="status-chip status-chip-neutral capitalize">{{ $template->channel === 'database' ? 'In-app' : $template->channel }}</span>
                                </x-table.td>
                                <x-table.td responsive="md" muted>{{ $template->subject ?: '—' }}</x-table.td>
                                <x-table.td><x-badge :status="$template->is_active ? 'active' : 'inactive'" /></x-table.td>
                                <x-table.td align="right">
                                    <div class="table-inline-actions justify-end">
                                        <button type="button" wire:click="edit({{ $template->id }})" class="table-action">Edit</button>
                                        <button type="button" wire:click="toggle({{ $template->id }})" class="table-action">
                                            {{ $template->is_active ? 'Pause' : 'Activate' }}
                                        </button>
                                        <button type="button" wire:click="delete({{ $template->id }})" wire:confirm="Delete this template?" class="table-action-danger">Delete</button>
                                    </div>
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="5">
                                <x-empty-state title="No templates" description="Add a template for an event code to customize notification copy.">
                                    <x-slot:actions>
                                        <x-button size="sm" wire:click="openForm">Add template</x-button>
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
            :title="$editingId ? 'Edit template' : 'Add template'"
            description="Use codes like incident.submitted. Placeholders depend on the event payload."
            width="lg"
            close-method="closeDrawer"
        >
            <x-drawer-form wire:submit.prevent="save" :submit-label="$editingId ? 'Update template' : 'Save template'" close-method="closeDrawer" target="save">
                <x-form-section title="Event">
                    <div class="sm:col-span-2">
                        <x-input wire:model="form.code" label="Event code *" list="notif-codes" placeholder="incident.submitted" />
                        <datalist id="notif-codes">
                            @foreach ($suggestedCodes as $code)
                                <option value="{{ $code }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <x-select wire:model="form.channel" label="Channel *" class="sm:col-span-2">
                        <option value="mail">Email</option>
                        <option value="sms">SMS</option>
                        <option value="database">In-app</option>
                    </x-select>
                </x-form-section>
                <x-form-section title="Copy">
                    <x-input wire:model="form.subject" label="Subject" class="sm:col-span-2" />
                    <x-textarea wire:model="form.body" label="Body *" rows="6" class="sm:col-span-2" />
                    <label class="sm:col-span-2 flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="checkbox" wire:model="form.is_active" class="rounded border-zinc-300 dark:border-zinc-600">
                        Active
                    </label>
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
