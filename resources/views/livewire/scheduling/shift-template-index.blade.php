<div>
    <x-page-shell title="Shift Templates" description="Reusable scheduling patterns across sites.">
        <x-slot:actions><x-button wire:click="openForm">New template</x-button></x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>

            <x-flash-status />

            <x-form-card title="Apply template to week" description="Creates open shifts for the selected week (Sunday–Saturday).">
                <div class="grid gap-3 sm:grid-cols-3">
                    <x-select wire:model="applyTemplateId" label="Template">
                        <option value="">Select</option>
                        @foreach($templates->where('is_active', true) as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="weekStart" type="date" label="Week containing (any day)" />
                    <div class="flex items-end">
                        <x-button wire:click="apply">Apply to week</x-button>
                    </div>
                </div>
                @error('applyTemplateId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('weekStart') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </x-form-card>

            <x-section-card title="Saved templates" class="mt-4" flush>
                @forelse($templates as $template)
                    <div class="list-row-start" wire:key="template-{{ $template->id }}">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $template->name }}</span>
                                @unless($template->is_active)
                                    <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium uppercase text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">Inactive</span>
                                @endunless
                            </div>
                            @if ($template->description)
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $template->description }}</div>
                            @endif
                            <div class="mt-1 text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ $template->items->count() }} shift pattern{{ $template->items->count() === 1 ? '' : 's' }}</div>
                            @if ($template->items->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach ($template->items as $item)
                                        <span class="status-chip status-chip-neutral text-[11px]">
                                            {{ ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$item->day_of_week] ?? $item->day_of_week }}
                                            {{ substr((string) $item->start_time, 0, 5) }}–{{ substr((string) $item->end_time, 0, 5) }}
                                            · {{ $item->site?->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="table-inline-actions shrink-0">
                            <button type="button" wire:click="toggleActive({{ $template->id }})" class="table-action">
                                {{ $template->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                            <button type="button" wire:click="editTemplate({{ $template->id }})" class="table-action">Edit</button>
                            <button type="button" wire:click="deleteTemplate({{ $template->id }})" wire:confirm="Delete this template?" class="table-action-danger">Delete</button>
                        </div>
                    </div>
                @empty
                    <div class="p-3">
                        <x-empty-state title="No templates" description="Create a template to quickly generate weekly shifts.">
                            <x-slot:actions>
                                <x-button size="sm" wire:click="openForm">New template</x-button>
                            </x-slot:actions>
                        </x-empty-state>
                    </div>
                @endforelse
            </x-section-card>
        </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($showForm)
        <x-drawer
            :title="$editingTemplateId ? 'Edit shift template' : 'Create shift template'"
            :description="$editingTemplateId ? 'Update the pattern rows, then re-apply to a week when ready.' : 'Define a weekly pattern of site shifts you can apply in one click.'"
            width="xl"
            close-method="closeDrawer"
        >
            <x-drawer-form wire:submit.prevent="save" :submit-label="$editingTemplateId ? 'Save changes' : 'Save template'" close-method="closeDrawer">
                <x-form-section title="Template">
                    <x-input wire:model="form.name" label="Name *" class="sm:col-span-2" />
                    <x-textarea wire:model="form.description" label="Description" rows="2" class="sm:col-span-2" />
                    <label class="sm:col-span-2 flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="checkbox" wire:model="form.is_active" class="rounded border-zinc-300 dark:border-zinc-600">
                        Active (inactive templates cannot be applied)
                    </label>
                </x-form-section>

                <x-form-section title="Shift patterns" description="One row per day/site window in the weekly cycle.">
                    <div class="sm:col-span-2 space-y-2">
                        @foreach($items as $i => $item)
                            <div class="template-item-row" wire:key="item-{{ $i }}">
                                <x-select wire:model="items.{{ $i }}.day_of_week" label="Day">
                                    @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d => $label)
                                        <option value="{{ $d }}">{{ $label }}</option>
                                    @endforeach
                                </x-select>
                                <x-input wire:model="items.{{ $i }}.start_time" type="time" label="Start" />
                                <x-input wire:model="items.{{ $i }}.end_time" type="time" label="End" />
                                <x-select wire:model="items.{{ $i }}.site_id" label="Site" class="sm:col-span-2">
                                    <option value="">Select</option>
                                    @foreach($sites as $site)
                                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                                    @endforeach
                                </x-select>
                                <x-input wire:model="items.{{ $i }}.required_guards" type="number" min="1" label="Guards" />
                                <div class="flex items-end gap-2">
                                    <x-input wire:model="items.{{ $i }}.billing_rate" type="number" step="0.01" label="Charge" class="min-w-0 flex-1" />
                                    @if (count($items) > 1)
                                        <button type="button" wire:click="removeItem({{ $i }})" class="mb-1 shrink-0 text-xs font-semibold text-red-600 hover:underline dark:text-red-400">Remove</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        <x-button type="button" size="sm" variant="secondary" wire:click="addItem">Add row</x-button>
                    </div>
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
