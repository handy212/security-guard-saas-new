<div>
    <x-page-shell
        :title="$isEditing ? 'Edit estimate' : 'New estimate'"
        description="Draft pricing for a client, then send and convert when accepted."
        :breadcrumbs="[
            ['label' => 'Back Office', 'href' => route('billing.hub')],
            ['label' => 'Estimates', 'href' => route('billing.estimates')],
            ['label' => $isEditing ? ($estimate->estimate_number ?? 'Edit') : 'New'],
        ]"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('billing.estimates')">Cancel</x-button>
            <x-button wire:click="save" wire:loading.attr="disabled">{{ $isEditing ? 'Save changes' : 'Create estimate' }}</x-button>
        </x-slot:actions>

        <x-flash-status type="success" />

        <form wire:submit="save" class="mx-auto max-w-3xl space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Client</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <x-select wire:model="form.client_account_id" label="Client *" class="sm:col-span-2">
                        <option value="">Select client</option>
                        @foreach ($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="form.valid_until" type="date" label="Valid until" class="sm:col-span-2" />
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Line items</h2>
                    <x-button type="button" size="sm" variant="secondary" wire:click="addLineItem">Add line</x-button>
                </div>

                <div class="space-y-3">
                    @foreach ($items as $i => $item)
                        <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="form-line-{{ $i }}">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Line {{ $i + 1 }}</span>
                                @if (count($items) > 1)
                                    <button type="button" wire:click="removeLineItem({{ $i }})" class="text-xs text-red-600 hover:underline">Remove</button>
                                @endif
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <x-input wire:model="items.{{ $i }}.description" label="Description *" class="sm:col-span-2" />
                                <x-input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" label="Qty *" />
                                <x-input wire:model="items.{{ $i }}.unit_price" type="number" step="0.01" label="Unit price (₦) *" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <x-button type="button" variant="secondary" :href="route('billing.estimates')">Cancel</x-button>
                <x-button type="submit">{{ $isEditing ? 'Save changes' : 'Create estimate' }}</x-button>
            </div>
        </form>
    </x-page-shell>
</div>
