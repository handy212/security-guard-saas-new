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

        <div class="stat-grid">
            <x-stat-card compact label="Templates" :value="$templates->total()" icon="plan" />
            <x-stat-card compact label="On page" :value="$templates->count()" icon="billing" />
            <x-stat-card compact label="Clients" :value="$clients->count()" icon="users" tone="info" />
            <x-stat-card compact label="Sites" :value="$sites->count()" icon="sites" tone="success" />
        </div>

        @if($showForm)
            <x-form-card title="Create template">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-input wire:model="form.name" label="Template name" required />
                    <x-select wire:model="form.client_account_id" label="Client (optional)">
                        <option value="">All clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </x-select>
                    <x-textarea wire:model="form.description" label="Description" class="sm:col-span-2" />
                </div>
                <div class="mt-4 space-y-2">
                    <div class="text-sm font-medium text-zinc-700">Fields</div>
                    @foreach($fields as $i => $field)
                        <div class="grid gap-2 sm:grid-cols-4">
                            <x-input wire:model="fields.{{ $i }}.label" label="Label" />
                            <x-select wire:model="fields.{{ $i }}.field_type" label="Type">
                                <option value="text">Text</option>
                                <option value="textarea">Textarea</option>
                                <option value="photo">Photo</option>
                                <option value="checkbox">Checkbox</option>
                                <option value="signature">Signature</option>
                                <option value="gps">GPS</option>
                            </x-select>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="fields.{{ $i }}.is_required" /> Required</label>
                        </div>
                    @endforeach
                    <x-button type="button" variant="secondary" size="sm" wire:click="addField">Add field</x-button>
                </div>
                <div class="mt-4 flex gap-2">
                    <x-button type="button" wire:click="save">Save template</x-button>
                    <x-button type="button" variant="secondary" wire:click="resetForm">Cancel</x-button>
                </div>
            </x-form-card>
        @endif

        <x-form-card title="Assign template to site" class="mt-4">
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
                <div class="flex items-end"><x-button wire:click="assignToSite">Assign</x-button></div>
            </div>
        </x-form-card>

        <x-section-card title="Templates" class="mt-4">
            @forelse($templates as $template)
                <div class="border-t border-zinc-100 py-3 first:border-0">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-medium">{{ $template->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $template->fields->count() }} fields · {{ $template->assignments->count() }} site assignments</div>
                        </div>
                        <div class="table-inline-actions shrink-0">
                            <button type="button" wire:click="edit({{ $template->id }})" class="table-action">Edit</button>
                            <button type="button" wire:click="delete({{ $template->id }})" wire:confirm="Delete this template?" class="table-action text-red-600">Delete</button>
                        </div>
                    </div>
                </div>
            @empty
                <x-empty-state title="No templates" description="Create a custom report template to get started." />
            @endforelse
            <x-pagination :paginator="$templates" />
        </x-section-card>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
