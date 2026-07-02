<div>
    <x-page-shell title="Shift Templates" description="Reusable scheduling patterns across sites.">
        <x-slot:actions><x-button wire:click="$set('showForm', true)">New template</x-button></x-slot:actions>

        <div class="stat-grid">
            <x-stat-card compact label="Templates" :value="$templates->count()" icon="plan" />
            <x-stat-card compact label="Sites" :value="$sites->count()" icon="sites" tone="info" />
            <x-stat-card compact label="Patterns" :value="$templates->sum(fn ($t) => $t->items->count())" icon="schedules" />
            <x-stat-card compact label="Rows" :value="count($items)" icon="billing" />
        </div>

        @if($showForm)
            <x-form-card title="Create shift template">
                <x-input wire:model="form.name" label="Name" class="mb-3" />
                <x-textarea wire:model="form.description" label="Description" class="mb-3" />
                @foreach($items as $i => $item)
                    <div class="mb-2 grid gap-2 sm:grid-cols-6 rounded-lg border p-2">
                        <x-select wire:model="items.{{ $i }}.day_of_week" label="Day">
                            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d => $label)
                                <option value="{{ $d }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                        <x-input wire:model="items.{{ $i }}.start_time" type="time" label="Start" />
                        <x-input wire:model="items.{{ $i }}.end_time" type="time" label="End" />
                        <x-select wire:model="items.{{ $i }}.site_id" label="Site">
                            <option value="">Select</option>
                            @foreach($sites as $site)<option value="{{ $site->id }}">{{ $site->name }}</option>@endforeach
                        </x-select>
                        <x-input wire:model="items.{{ $i }}.required_guards" type="number" label="Guards" />
                        <x-input wire:model="items.{{ $i }}.billing_rate" type="number" step="0.01" label="Rate" />
                    </div>
                @endforeach
                <div class="flex gap-2 mt-3">
                    <x-button size="sm" variant="secondary" wire:click="addItem">Add row</x-button>
                    <x-button wire:click="save">Save</x-button>
                </div>
            </x-form-card>
        @endif

        <x-form-card title="Apply template" class="mt-4">
            <div class="grid gap-3 sm:grid-cols-3">
                <x-select wire:model="applyTemplateId" label="Template">
                    <option value="">Select</option>
                    @foreach($templates as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                </x-select>
                <x-input wire:model="weekStart" type="date" label="Week start" />
                <div class="flex items-end"><x-button wire:click="apply">Apply</x-button></div>
            </div>
        </x-form-card>

        <x-section-card title="Templates" class="mt-4">
            @forelse($templates as $template)
                <div class="border-t py-2 first:border-0">
                    <div class="font-medium">{{ $template->name }}</div>
                    <div class="text-xs text-zinc-500">{{ $template->items->count() }} shift patterns</div>
                </div>
            @empty
                <x-empty-state title="No templates" />
            @endforelse
        </x-section-card>
    </x-page-shell>
</div>
