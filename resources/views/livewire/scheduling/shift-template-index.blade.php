<div>
    <x-page-shell title="Shift Templates" description="Reusable scheduling patterns across sites.">
        <x-slot:actions><x-button wire:click="openForm">New template</x-button></x-slot:actions>
        <x-schedules-nav />
        <x-flash-status />

        @if($showForm)
            <x-form-card :title="$editingTemplateId ? 'Edit shift template' : 'Create shift template'" class="mb-4">
                <x-input wire:model="form.name" label="Name" class="mb-3" />
                <x-textarea wire:model="form.description" label="Description" class="mb-3" />
                <label class="mb-3 flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="form.is_active" class="rounded border-zinc-300">
                    Active (inactive templates cannot be applied)
                </label>
                @foreach($items as $i => $item)
                    <div class="mb-2 grid gap-2 rounded-lg border border-zinc-200 p-3 sm:grid-cols-7" wire:key="item-{{ $i }}">
                        <x-select wire:model="items.{{ $i }}.day_of_week" label="Day">
                            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d => $label)
                                <option value="{{ $d }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                        <x-input wire:model="items.{{ $i }}.start_time" type="time" label="Start" />
                        <x-input wire:model="items.{{ $i }}.end_time" type="time" label="End" />
                        <x-select wire:model="items.{{ $i }}.site_id" label="Site" class="sm:col-span-2">
                            <option value="">Select</option>
                            @foreach($sites as $site)<option value="{{ $site->id }}">{{ $site->name }}</option>@endforeach
                        </x-select>
                        <x-input wire:model="items.{{ $i }}.required_guards" type="number" min="1" label="Guards" />
                        <div class="flex items-end gap-2">
                            <x-input wire:model="items.{{ $i }}.billing_rate" type="number" step="0.01" label="Rate" />
                            @if (count($items) > 1)
                                <button type="button" wire:click="removeItem({{ $i }})" class="mb-1 text-xs text-red-600 hover:underline">Remove</button>
                            @endif
                        </div>
                    </div>
                @endforeach
                <div class="mt-3 flex gap-2">
                    <x-button size="sm" variant="secondary" wire:click="addItem">Add row</x-button>
                    <x-button wire:click="save">{{ $editingTemplateId ? 'Save changes' : 'Save template' }}</x-button>
                    <x-button variant="secondary" wire:click="$set('showForm', false)">Cancel</x-button>
                </div>
            </x-form-card>
        @endif

        <x-form-card title="Apply template to week" description="Creates open shifts for the selected week (Sunday–Saturday).">
            <div class="grid gap-3 sm:grid-cols-3">
                <x-select wire:model="applyTemplateId" label="Template">
                    <option value="">Select</option>
                    @foreach($templates->where('is_active', true) as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                </x-select>
                <x-input wire:model="weekStart" type="date" label="Week containing (any day)" />
                <div class="flex items-end"><x-button wire:click="apply">Apply to week</x-button></div>
            </div>
            @error('applyTemplateId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('weekStart') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </x-form-card>

        <x-section-card title="Saved templates" class="mt-4">
            @forelse($templates as $template)
                <div class="border-t border-zinc-100 py-3 first:border-0" wire:key="template-{{ $template->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-zinc-900">{{ $template->name }}</span>
                                @unless($template->is_active)
                                    <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium uppercase text-zinc-500">Inactive</span>
                                @endunless
                            </div>
                            @if ($template->description)
                                <div class="text-xs text-zinc-500">{{ $template->description }}</div>
                            @endif
                            <div class="mt-1 text-xs text-zinc-500">{{ $template->items->count() }} shift pattern{{ $template->items->count() === 1 ? '' : 's' }}</div>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <button type="button" wire:click="toggleActive({{ $template->id }})" class="text-xs font-medium text-zinc-600 hover:text-zinc-900">
                                {{ $template->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                            <button type="button" wire:click="editTemplate({{ $template->id }})" class="text-xs font-medium text-zinc-600 hover:text-zinc-900">Edit</button>
                            <button type="button" wire:click="deleteTemplate({{ $template->id }})" wire:confirm="Delete this template?" class="text-xs font-medium text-red-600 hover:text-red-800">Delete</button>
                        </div>
                    </div>
                    @if ($template->items->isNotEmpty())
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach ($template->items as $item)
                                <span class="rounded bg-zinc-100 px-2 py-0.5 text-[11px] text-zinc-600">
                                    {{ ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$item->day_of_week] ?? $item->day_of_week }}
                                    {{ substr((string) $item->start_time, 0, 5) }}–{{ substr((string) $item->end_time, 0, 5) }}
                                    · {{ $item->site?->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <x-empty-state title="No templates" description="Create a template to quickly generate weekly shifts." />
            @endforelse
        </x-section-card>
    </x-page-shell>
</div>
