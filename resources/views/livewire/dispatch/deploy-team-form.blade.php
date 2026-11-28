<x-section-card title="Deploy a team" compact>
    @if ($teams->isEmpty())
        <x-empty-state inline title="No teams available to deploy" :description="$active->isNotEmpty() ? 'Close a current duty to deploy that team again, or set up another team.' : 'Set up a response team or finish any outstanding calls in Dispatch.'">
            @can('settings.manage')
                <x-slot:actions>
                    <x-button size="sm" variant="secondary" :href="route('dispatch.deployments', ['tab' => 'teams'])" wire:navigate>Manage teams</x-button>
                </x-slot:actions>
            @endcan
        </x-empty-state>
    @else
    <form wire:submit="deploy" class="space-y-2">
        <div class="grid gap-2 md:grid-cols-3">
            <x-select wire:model.live="teamId" label="Team">
                <option value="">Select team</option>
                @foreach ($teams as $team)
                    <option value="{{ $team->id }}" @disabled(! $team->standbyPoint?->region?->is_active || ! $team->standbyPoint?->radius_meters)>{{ $team->name }} · {{ $team->standbyPoint?->name ?? 'No standby point' }}{{ $team->standbyPoint?->radius_meters ? '' : ' · Radius required' }}</option>
                @endforeach
            </x-select>
            <x-input wire:model="location" label="Handover location" />
            <x-input wire:model="end" label="Planned end" type="datetime-local" />
        </div>
        @if ($teamId)
            @php $reportingOfficers = $teamOfficers->whereIn('id', $officers); @endphp
            <div class="grid items-end gap-2 md:grid-cols-3">
                <fieldset class="space-y-1 md:col-span-2">
                    <legend class="text-sm font-medium">Officers reporting for duty</legend>
                    @foreach ($teamOfficers as $officer)
                        <label class="inline-flex items-center gap-2 pr-3 text-sm"><input type="checkbox" wire:model.live="officers" value="{{ $officer->id }}"> {{ $officer->full_name }}</label>
                    @endforeach
                    @error('officers') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </fieldset>
                <x-select wire:model="custodian" label="Duty lead">
                    <option value="">Select reporting officer</option>
                    @foreach ($reportingOfficers as $officer)<option value="{{ $officer->id }}">{{ $officer->full_name }}</option>@endforeach
                </x-select>
            </div>
            <div class="space-y-2 border-t border-zinc-200 pt-2 dark:border-zinc-800">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold">Kit checkout</h3>
                    <x-button type="button" size="sm" variant="secondary" wire:click="addKitLine"
                        :disabled="$reportingOfficers->isEmpty() || $assets->isEmpty() || count($kit) >= 100">Add item</x-button>
                </div>
                @if ($kit === [])
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $reportingOfficers->isEmpty() ? 'Select the officers reporting for duty first.' : ($assets->isEmpty() ? 'No equipment available to issue.' : 'No items to issue. Add a row for each officer receiving kit.') }}</p>
                @else
                    <x-data-table>
                        <x-table.head><tr>
                            <x-table.th>Item</x-table.th><x-table.th>Issue to</x-table.th>
                            <x-table.th>Qty</x-table.th><x-table.th>Condition</x-table.th><x-table.th align="right">Action</x-table.th>
                        </tr></x-table.head>
                        <tbody>
                            @foreach ($kit as $line => $item)
                                <tr wire:key="checkout-line-{{ $line }}">
                                    <x-table.td class="min-w-48">
                                        <x-select wire:model="kit.{{ $line }}.asset_id" aria-label="Item">
                                            <option value="">Select equipment</option>
                                            @foreach ($assets as $asset)
                                                <option value="{{ $asset->id }}">{{ $asset->displayLabel() }}{{ $asset->assetCategory?->isConsumable() ? ' · '.$asset->quantity_on_hand.' available' : '' }}</option>
                                            @endforeach
                                        </x-select>
                                    </x-table.td>
                                    <x-table.td class="min-w-44">
                                        <x-select wire:model="kit.{{ $line }}.guard_id" aria-label="Issue to">
                                            <option value="">Select reporting officer</option>
                                            @foreach ($reportingOfficers as $officer)<option value="{{ $officer->id }}">{{ $officer->full_name }}</option>@endforeach
                                        </x-select>
                                    </x-table.td>
                                    <x-table.td class="w-24"><x-input wire:model="kit.{{ $line }}.quantity" aria-label="Quantity" type="number" min="1" /></x-table.td>
                                    <x-table.td class="min-w-36">
                                        <x-select wire:model="kit.{{ $line }}.condition" aria-label="Condition at issue">
                                            <option value="">Select condition</option>
                                            <option value="Good">Good</option>
                                            <option value="Fair">Fair</option>
                                            <option value="Poor">Poor</option>
                                        </x-select>
                                    </x-table.td>
                                    <x-table.td align="right"><button type="button" class="table-action" wire:click="removeKitLine({{ $line }})">Remove</button></x-table.td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-data-table>
                @endif
            </div>
        @endif
        <x-button type="submit" size="sm" :disabled="! $teamId" loading-text="Deploying…">Deploy team</x-button>
    </form>
    @endif
</x-section-card>
