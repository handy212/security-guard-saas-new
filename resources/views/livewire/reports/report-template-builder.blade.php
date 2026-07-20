<div>
    <x-page-shell
        title="Custom Report Templates"
        description="Build and assign custom report forms to post sites."
        :breadcrumbs="[
            ['label' => 'Reports', 'href' => route('reports.hub')],
            ['label' => 'Templates'],
        ]"
    >
        <x-slot:actions>
            <x-button wire:click="openCreate">New template</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-reports-nav /></x-slot:sidebar>

            <x-flash-status />

            <div class="stat-grid">
                <x-stat-card compact label="Templates" :value="$templates->total()" icon="plan" />
                <x-stat-card compact label="On page" :value="$templates->count()" icon="billing" />
                <x-stat-card compact label="Clients" :value="$clients->count()" icon="users" tone="info" />
                <x-stat-card compact label="Sites" :value="$sites->count()" icon="sites" tone="success" />
            </div>

            <x-section-card title="Assign template to site" description="Link a saved template so field officers see it at that site.">
                <div class="grid gap-3 sm:grid-cols-3">
                    <x-select wire:model="assignTemplateId" label="Template">
                        <option value="">Select</option>
                        @foreach($templates as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model="assignSiteId" label="Site">
                        <option value="">Select</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                    <div class="flex items-end">
                        <x-button wire:click="assignToSite" wire:loading.attr="disabled" wire:target="assignToSite" :disabled="! $assignTemplateId || ! $assignSiteId">
                            <span wire:loading.remove wire:target="assignToSite">Assign</span>
                            <span wire:loading wire:target="assignToSite">Assigning…</span>
                        </x-button>
                    </div>
                </div>
                @error('assignTemplateId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('assignSiteId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </x-section-card>

            <x-section-card title="Templates" class="mt-4" :description="$templates->total().' custom form'.($templates->total() === 1 ? '' : 's')" flush>
                @forelse($templates as $template)
                    <div class="list-row-start" wire:key="tpl-{{ $template->id }}">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $template->name }}</span>
                                @unless($template->is_active)
                                    <span class="status-chip status-chip-neutral">Inactive</span>
                                @endunless
                            </div>
                            @if ($template->description)
                                <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ Str::limit($template->description, 80) }}</div>
                            @endif
                            <div class="mt-1.5 flex flex-wrap gap-1.5 text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                                <span class="status-chip status-chip-neutral">{{ $template->fields->count() }} field{{ $template->fields->count() === 1 ? '' : 's' }}</span>
                                <span class="status-chip status-chip-neutral">{{ $template->assignments->count() }} site{{ $template->assignments->count() === 1 ? '' : 's' }}</span>
                            </div>
                            @if ($template->assignments->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach ($template->assignments->take(4) as $assignment)
                                        <span class="guard-chip">{{ $assignment->site?->name ?? 'Site' }}</span>
                                    @endforeach
                                    @if ($template->assignments->count() > 4)
                                        <span class="status-chip status-chip-neutral">+{{ $template->assignments->count() - 4 }} more</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="table-inline-actions shrink-0">
                            <button type="button" wire:click="edit({{ $template->id }})" class="table-action">Edit</button>
                            <button type="button" wire:click="delete({{ $template->id }})" wire:confirm="Delete this template?" class="table-action-danger">Delete</button>
                        </div>
                    </div>
                @empty
                    <div class="p-3">
                        <x-empty-state title="No templates" description="Create a custom report template to get started.">
                            <x-slot:actions>
                                <x-button size="sm" wire:click="openCreate">New template</x-button>
                            </x-slot:actions>
                        </x-empty-state>
                    </div>
                @endforelse
                <div class="border-t border-zinc-100 px-4 py-2 dark:border-zinc-800">
                    <x-pagination :paginator="$templates" />
                </div>
            </x-section-card>
        </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($showForm)
        <x-drawer
            :title="$editingId ? 'Edit template' : 'Create template'"
            :description="$editingId ? 'Update fields and assignment scope for this form.' : 'Define the fields officers fill out on site reports.'"
            width="xl"
            close-method="closeDrawer"
        >
            <x-drawer-form wire:submit.prevent="save" :submit-label="$editingId ? 'Save changes' : 'Save template'" close-method="closeDrawer" target="save">
                <x-form-section title="Template">
                    <x-input wire:model="form.name" label="Template name *" class="sm:col-span-2" />
                    <x-select wire:model="form.client_account_id" label="Client (optional)" class="sm:col-span-2">
                        <option value="">All clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </x-select>
                    <x-textarea wire:model="form.description" label="Description" rows="2" class="sm:col-span-2" />
                    <label class="sm:col-span-2 flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="checkbox" wire:model="form.is_active" class="rounded border-zinc-300 dark:border-zinc-600">
                        Active
                    </label>
                </x-form-section>

                <x-form-section title="Fields" description="Each row becomes an input on the field report form.">
                    <div class="sm:col-span-2 space-y-2">
                        @foreach($fields as $i => $field)
                            <div class="grid gap-2 rounded-md border border-zinc-200/90 bg-zinc-50/50 p-3 sm:grid-cols-4 dark:border-zinc-800 dark:bg-zinc-900/40" wire:key="field-{{ $i }}">
                                <x-input wire:model="fields.{{ $i }}.label" label="Label *" class="sm:col-span-2" />
                                <x-select wire:model="fields.{{ $i }}.field_type" label="Type">
                                    <option value="text">Text</option>
                                    <option value="textarea">Textarea</option>
                                    <option value="photo">Photo</option>
                                    <option value="checkbox">Checkbox</option>
                                    <option value="signature">Signature</option>
                                    <option value="gps">GPS</option>
                                </x-select>
                                <div class="flex items-end justify-between gap-2 pb-1">
                                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                        <input type="checkbox" wire:model="fields.{{ $i }}.is_required" class="rounded border-zinc-300 dark:border-zinc-600">
                                        Required
                                    </label>
                                    @if (count($fields) > 1)
                                        <button type="button" wire:click="removeField({{ $i }})" class="text-xs font-semibold text-red-600 hover:underline dark:text-red-400">Remove</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        <x-button type="button" variant="secondary" size="sm" wire:click="addField">Add field</x-button>
                    </div>
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
