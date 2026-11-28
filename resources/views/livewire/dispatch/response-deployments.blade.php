<div>
    <x-page-shell title="Dispatch duty" compact stable-header>
        <x-slot:actions>
            @can('dispatch.manage')
                <x-button size="sm" variant="secondary" :href="route('dispatch.control-room')" wire:navigate>Dispatch Center</x-button>
            @endcan
        </x-slot:actions>
        <nav aria-label="Dispatch duty sections" class="flex flex-wrap gap-1 border-b border-zinc-200 dark:border-zinc-800">
            @foreach (['deployments' => 'Duty', 'regions' => 'Regions', 'standby' => 'Standby points', 'teams' => 'Teams'] as $key => $label)
                @can($key === 'deployments' ? 'dispatch.manage' : 'settings.manage')
                    <a href="{{ route('dispatch.deployments', $key === 'deployments' ? [] : ['tab' => $key]) }}" wire:navigate
                        @if ($tab === $key) aria-current="page" @endif
                        @class(['border-b-2 px-3 py-1.5 text-sm font-medium', 'border-accent-600 text-accent-700 dark:text-accent-300' => $tab === $key, 'border-transparent text-zinc-600 dark:text-zinc-400' => $tab !== $key])>
                        {{ $label }}
                    </a>
                @endcan
            @endforeach
        </nav>
        @if ($tab !== 'deployments')
            <livewire:settings.branch-index :section="$tab" :key="'deployment-settings-'.$tab" />
        @else
        <x-flash-status type="success" />
        @error('deployment') <x-alert tone="danger">{{ $message }}</x-alert> @enderror

        @if ($active->isEmpty())
            @include('livewire.dispatch.deploy-team-form')
        @endif

        <x-data-table title="Teams on duty">
            <x-table.head>
                <tr>
                    <x-table.th>Team</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th>Standby</x-table.th>
                    <x-table.th responsive="lg">Duty lead</x-table.th>
                    <x-table.th responsive="lg">Planned end</x-table.th>
                    <x-table.th align="right">Actions</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
            @forelse ($active as $deployment)
                @php
                    $busy = $deployment->team->outstandingDispatches->isNotEmpty();
                    $dutyStatus = $deployment->ending_at ? 'Ending duty' : ($busy ? 'Assigned to a call' : ($deployment->arrived_at ? 'Ready' : 'En route to standby'));
                @endphp
                <tr id="duty-team-{{ $deployment->team->id }}" wire:key="response-duty-{{ $deployment->id }}" class="table-row-hover">
                    <x-table.td>
                        <span class="font-medium">{{ $deployment->team->name }}</span>
                        <span class="block text-xs text-zinc-500 dark:text-zinc-400">Handover: {{ $deployment->handover_location }}</span>
                    </x-table.td>
                    <x-table.td><x-badge :status="$dutyStatus" :map="['Ready' => 'success', 'Ending duty' => 'warning', 'Assigned to a call' => 'info']" /></x-table.td>
                    <x-table.td>{{ $deployment->standby_location['name'] }}</x-table.td>
                    <x-table.td responsive="lg">{{ $deployment->custodian?->full_name ?? '—' }}</x-table.td>
                    <x-table.td responsive="lg" class="tabular-nums">{{ $deployment->planned_end_at->format('M j, H:i') }}</x-table.td>
                    <x-table.td align="right">
                        @if (! $busy && ! $deployment->ending_at)
                            <x-button size="sm" variant="secondary" wire:click="beginHandover({{ $deployment->id }})" wire:confirm="Take this team out of dispatch availability and start handover?">Start handover</x-button>
                        @elseif ($busy && ! $deployment->ending_at)
                            <x-button size="sm" variant="secondary" :href="route('dispatch.control-room', ['dispatch' => $deployment->team->outstandingDispatches->first()->id])" wire:navigate>View call</x-button>
                        @endif
                    </x-table.td>
                </tr>
                @if ($deployment->ending_at || $deployment->kit->isNotEmpty() || (! $deployment->arrived_at && auth()->user()->can('settings.manage')) || ($busy && $ready->isNotEmpty()))
                <tr wire:key="response-duty-details-{{ $deployment->id }}">
                <td colspan="6" class="space-y-2 px-4 py-2">
                    @if (! $deployment->ending_at)
                        @if ($deployment->kit->isNotEmpty())
                            <details class="text-sm" wire:ignore.self>
                                <summary class="page-link cursor-pointer">Issued kit ({{ $deployment->kit->count() }})</summary>
                                <ul class="mt-1 space-y-1">
                                    @foreach ($deployment->kit as $item)
                                        <li>{{ $item->asset?->displayLabel() }} × {{ $item->quantity }} · {{ $item->assignedGuard?->full_name ?? 'No recorded holder' }} · {{ $item->issue_notes }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    @endif
                    @if (! $deployment->arrived_at && ! $deployment->ending_at && auth()->user()->can('settings.manage'))
                        <form wire:submit="confirmArrival({{ $deployment->id }})" class="flex flex-wrap items-end gap-2">
                            <x-input wire:model="arrivalReasons.{{ $deployment->id }}" label="Manual arrival reason" />
                            <x-button size="sm" variant="secondary" type="submit">Record arrival</x-button>
                        </form>
                    @endif
                    @if ($deployment->ending_at)
                        <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                            <h4 class="text-sm font-semibold">Receive kit at {{ $deployment->handover_location }}</h4>
                            @if ($deployment->kit->isEmpty())
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">No kit to receive. Close duty to make the team available for its next deployment.</p>
                            @endif
                            <form wire:submit="closeDuty({{ $deployment->id }})" class="mt-2 space-y-2">
                                @foreach ($deployment->kit as $item)
                                    <fieldset class="grid items-end gap-2 sm:grid-cols-2" wire:key="return-item-{{ $item->id }}">
                                        <legend class="text-sm">{{ $item->asset?->displayLabel() }} × {{ $item->quantity }} · {{ $item->assignedGuard?->full_name ?? 'No recorded holder' }} · Issued condition: {{ $item->issue_notes }}</legend>
                                        <x-select wire:model="returns.{{ $deployment->id }}.{{ $item->id }}.outcome" label="Outcome">
                                            <option value="">Choose outcome</option><option value="returned">Returned</option>
                                            @if ($item->asset?->assetCategory?->isConsumable())
                                                <option value="consumed">Consumed</option>
                                            @else
                                                <option value="damaged">Damaged</option><option value="missing">Missing</option>
                                            @endif
                                        </x-select>
                                        <x-input wire:model="returns.{{ $deployment->id }}.{{ $item->id }}.notes" label="Condition or exception notes" />
                                    </fieldset>
                                @endforeach
                                <x-button type="submit" size="sm" :loading-text="'Closing duty…'">Close duty</x-button>
                            </form>
                        </div>
                    @elseif ($busy && $ready->isNotEmpty())
                        @foreach ($deployment->team->outstandingDispatches as $call)
                            @if (in_array($call->status, [\App\Enums\DispatchStatus::ASSIGNED, \App\Enums\DispatchStatus::EN_ROUTE, \App\Enums\DispatchStatus::ON_SCENE], true))
                                <details wire:key="handover-call-{{ $call->id }}" wire:ignore.self>
                                    <summary class="page-link cursor-pointer text-sm">Hand over {{ $call->dispatch_number }}</summary>
                                    <form wire:submit="handOverCall({{ $call->id }})" class="mt-2 flex flex-wrap items-end gap-2">
                                        <x-select wire:model="callHandovers.{{ $call->id }}.team" label="Receiving team">
                                            <option value="">Choose ready team</option>
                                            @foreach ($ready as $receiver)<option value="{{ $receiver->team->id }}">{{ $receiver->team->name }}</option>@endforeach
                                        </x-select>
                                        <x-input wire:model="callHandovers.{{ $call->id }}.reason" label="Handover reason" />
                                        <x-button type="submit" size="sm" variant="secondary">Transfer call</x-button>
                                    </form>
                                </details>
                            @endif
                        @endforeach
                    @endif
                </td>
                </tr>
                @endif
            @empty
                <x-table.empty colspan="6"><x-empty-state inline title="No teams on duty" /></x-table.empty>
            @endforelse
            </tbody>
        </x-data-table>

        @if ($active->isNotEmpty())
            @include('livewire.dispatch.deploy-team-form')
        @endif

        <x-data-table title="Recent handovers">
            <x-table.head>
                <tr>
                    <x-table.th>Team</x-table.th>
                    <x-table.th>Handover location</x-table.th>
                    <x-table.th responsive="lg">Duty lead</x-table.th>
                    <x-table.th>Closed</x-table.th>
                    <x-table.th>Kit record</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
            @forelse ($history as $deployment)
                <tr wire:key="closed-duty-{{ $deployment->id }}" class="table-row-hover">
                    <x-table.td class="font-medium">{{ $deployment->team->name }}</x-table.td>
                    <x-table.td>{{ $deployment->handover_location }}</x-table.td>
                    <x-table.td responsive="lg">{{ $deployment->custodian?->full_name ?? '—' }}</x-table.td>
                    <x-table.td class="tabular-nums">{{ $deployment->closed_at->format('M j, H:i') }}</x-table.td>
                    <x-table.td>
                        @if ($deployment->kit->isNotEmpty())
                            <details wire:ignore.self>
                                <summary class="page-link cursor-pointer">{{ $deployment->kit->count() }} kit {{ Str::plural('item', $deployment->kit->count()) }}</summary>
                                @foreach ($deployment->kit as $item)
                                    <p class="mt-1 text-xs">{{ $item->asset?->displayLabel() }} × {{ $item->quantity }} · {{ $item->assignedGuard?->full_name ?? 'No recorded holder' }} · {{ Str::headline($item->status) }} {{ $item->return_notes }}</p>
                                @endforeach
                            </details>
                        @else
                            <span class="text-xs text-zinc-500">No kit issued</span>
                        @endif
                    </x-table.td>
                </tr>
            @empty
                <x-table.empty colspan="5"><x-empty-state inline title="No completed handovers" /></x-table.empty>
            @endforelse
            </tbody>
        </x-data-table>
        @endif
    </x-page-shell>
</div>
