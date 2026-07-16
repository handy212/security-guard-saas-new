<div>
    <x-page-shell title="Notification templates" description="Customize email and in-app notification copy for operational events.">
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <x-flash-status />

            <x-form-card :title="$editingId ? 'Edit template' : 'Add template'" description="Use codes like incident.submitted. Placeholders depend on the event payload.">
                <form wire:submit="save" class="space-y-3">
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-input wire:model="form.code" label="Event code" list="notif-codes" placeholder="incident.submitted" />
                        <datalist id="notif-codes">
                            @foreach ($suggestedCodes as $code)
                                <option value="{{ $code }}"></option>
                            @endforeach
                        </datalist>
                        <x-select wire:model="form.channel" label="Channel">
                            <option value="mail">Email</option>
                            <option value="sms">SMS</option>
                            <option value="database">In-app</option>
                        </x-select>
                    </div>
                    <x-input wire:model="form.subject" label="Subject" />
                    <x-textarea wire:model="form.body" label="Body" rows="5" />
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="form.is_active" class="rounded border-zinc-300">
                        Active
                    </label>
                    <div class="flex gap-2">
                        <x-button type="submit">{{ $editingId ? 'Update' : 'Save' }} template</x-button>
                        @if ($editingId)
                            <x-button type="button" variant="secondary" wire:click="cancelEdit">Cancel</x-button>
                        @endif
                    </div>
                </form>
            </x-form-card>

            <x-data-table title="Templates" class="mt-4">
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
                            <x-table.td class="font-mono text-sm">{{ $template->code }}</x-table.td>
                            <x-table.td muted>{{ $template->channel }}</x-table.td>
                            <x-table.td responsive="md" muted>{{ $template->subject ?: '—' }}</x-table.td>
                            <x-table.td><x-badge :status="$template->is_active ? 'active' : 'inactive'" /></x-table.td>
                            <x-table.td align="right">
                                <div class="table-inline-actions">
                                    <button type="button" wire:click="edit({{ $template->id }})" class="table-action">Edit</button>
                                    <button type="button" wire:click="toggle({{ $template->id }})" class="table-action">
                                        {{ $template->is_active ? 'Pause' : 'Activate' }}
                                    </button>
                                    <button type="button" wire:click="delete({{ $template->id }})" wire:confirm="Delete this template?" class="table-action text-red-600">Delete</button>
                                </div>
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="5">
                            <x-empty-state title="No templates" description="Add a template for an event code above." />
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
