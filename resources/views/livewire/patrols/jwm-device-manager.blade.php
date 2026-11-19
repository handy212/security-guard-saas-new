<div>
    <x-page-shell title="Patrol readers" description="Register shared readers and control how device records identify guards and checkpoints.">
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('patrols.index') }}">Patrol board</x-button>
            @if ($ready)<x-button wire:click="openCreate" data-reader-action="register">Register reader</x-button>@endif
        </x-slot:actions>

        <div role="status" aria-live="polite"><x-flash-status type="success" /></div>
        <p class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
            Configuration only. No reader commands or automatic uploads are enabled. Fingerprint enrollment still requires a verified vendor contract.
        </p>

        @if (! $ready)
            <section class="card-surface p-5">
                <h2 class="font-semibold">Database setup required</h2>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Ask your administrator to apply the two reviewed JWM migrations before managing readers. No migration runs from this screen.</p>
            </section>
        @else
            <div class="grid min-w-0 gap-4 xl:grid-cols-3">
                <section class="min-w-0 space-y-3" aria-label="Registered readers">
                    <x-input wire:model.live.debounce.300ms="search" label="Find a reader" type="search" placeholder="Name or reader number" />
                    <div class="card-surface divide-y divide-zinc-100 overflow-hidden dark:divide-zinc-800">
                        @forelse ($readers as $reader)
                            <button type="button" wire:key="reader-{{ $reader->id }}" wire:click="selectDevice({{ $reader->id }})"
                                aria-pressed="{{ $device?->id === $reader->id ? 'true' : 'false' }}"
                                @class(['w-full min-w-0 p-4 text-left transition hover:bg-zinc-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-accent-600 dark:hover:bg-zinc-800',
                                    'bg-accent-50 dark:bg-zinc-800' => $device?->id === $reader->id])>
                                <span class="block break-words font-medium text-zinc-900 dark:text-zinc-100">{{ $reader->name }}</span>
                                <span class="mt-1 block break-all font-mono text-xs text-zinc-500 dark:text-zinc-400">{{ $reader->reader_number }}</span>
                                <span class="mt-2 block text-xs text-zinc-600 dark:text-zinc-300">{{ $reader->is_active ? 'Enabled' : 'Disabled' }} &middot; {{ $reader->require_device_identity ? 'Strict identification' : 'Handover-based' }}</span>
                            </button>
                        @empty
                            <div class="p-4 text-sm text-zinc-500" role="status">{{ $search !== '' ? 'No matching readers. Try a different name or number.' : 'No readers registered. Register your first reader to get started.' }}</div>
                        @endforelse
                    </div>
                    <x-pagination :paginator="$readers" />
                </section>

                <section class="min-w-0 space-y-4 xl:col-span-2" aria-label="Reader configuration" wire:loading.attr="aria-busy">
                    @if ($device)
                        <div class="card-surface p-4 sm:p-5" wire:key="reader-detail-{{ $device->id }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h2 class="break-words text-lg font-semibold">{{ $device->name }}</h2>
                                    <p class="mt-1 break-all font-mono text-xs text-zinc-500 dark:text-zinc-400">{{ $device->model }} / {{ $device->reader_number }}</p>
                                </div>
                                <x-button size="sm" variant="secondary" wire:click="openEditor('settings')" data-reader-action="settings">Reader settings</x-button>
                            </div>
                            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                                <div><dt class="text-zinc-500 dark:text-zinc-400">Ingestion</dt><dd class="mt-1 font-medium">{{ $device->is_active ? 'Enabled' : 'Disabled' }}</dd></div>
                                <div><dt class="text-zinc-500 dark:text-zinc-400">Checkpoint identity</dt><dd class="mt-1 font-medium">{{ $device->require_device_identity ? 'Strict identification' : 'Handover-based' }}</dd></div>
                                <div><dt class="text-zinc-500 dark:text-zinc-400">Connection</dt><dd class="mt-1 font-medium">Not monitored</dd></div>
                            </dl>
                            <p class="mt-4 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                                {{ $device->require_device_identity ? 'Checkpoint uploads must carry mapped guard and checkpoint IDs. The guard must agree with the handover at the event time.' : 'Records use the guard handover at the original event time. This does not verify a fingerprint identity.' }}
                                GPS without an external guard ID remains handover-based.
                            </p>
                            @unless ($device->is_active)
                                <p class="mt-3 text-sm text-amber-800 dark:text-amber-200" role="status">This reader is disabled. Re-enable it before adding handovers or mappings. A new bridge credential is required after re-enabling.</p>
                            @endunless
                        </div>

                        <div class="flex flex-wrap gap-2" role="group" aria-label="Configuration history">
                            @foreach (['handover' => 'Guard handovers', 'guard' => 'Guard IDs', 'checkpoint' => 'Checkpoint IDs'] as $value => $label)
                                <button type="button" wire:click="$set('tab', '{{ $value }}')" aria-pressed="{{ $tab === $value ? 'true' : 'false' }}"
                                    @class(['rounded-md border px-3 py-2 text-sm font-medium focus-visible:outline focus-visible:outline-2 focus-visible:outline-accent-600',
                                        'border-accent-600 bg-accent-600 text-white' => $tab === $value,
                                        'border-zinc-200 bg-white text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300' => $tab !== $value])>{{ $label }}</button>
                            @endforeach
                        </div>
                        <div class="card-surface overflow-hidden">
                            <div class="card-header flex-wrap gap-3">
                                <h2 class="card-header-title">{{ ['handover' => 'Guard handovers', 'guard' => 'External guard IDs', 'checkpoint' => 'External checkpoint IDs'][$tab] }}</h2>
                                @if ($device->is_active)
                                    <x-button size="sm" wire:click="openEditor('{{ $tab }}')" data-reader-action="{{ $tab }}">{{ $tab === 'handover' ? 'Record handover' : 'Add mapping' }}</x-button>
                                @endif
                            </div>
                            <p class="px-4 pb-3 text-xs text-zinc-500 dark:text-zinc-400">{{ $tab === 'handover' ? 'Immutable intervals, shown in UTC. Start included; end excluded. Overlapping handovers are rejected.' : 'Exact IDs: case and leading zeroes matter. Mappings cannot be edited or rebound, even after a target is deleted.' }}</p>
                            <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @forelse ($entries as $entry)
                                    <li class="space-y-1 px-4 py-3 text-sm" wire:key="{{ $tab }}-entry-{{ $entry->id }}">
                                        @if ($tab === 'handover')
                                            <p class="font-medium">{{ $entry->assignedGuard ? $entry->assignedGuard->first_name.' '.$entry->assignedGuard->last_name : 'Guard unavailable' }}</p>
                                            <p class="break-words text-xs text-zinc-500 dark:text-zinc-400">{{ $entry->starts_at->format('d M Y H:i:s') }} to {{ $entry->ends_at->format('d M Y H:i:s') }} UTC</p>
                                        @else
                                            <p class="break-all font-mono font-medium">{{ $entry->external_id }}</p>
                                            <p class="break-words text-zinc-600 dark:text-zinc-300">{{ $tab === 'guard' ? ($entry->assignedGuard ? $entry->assignedGuard->first_name.' '.$entry->assignedGuard->last_name.' / '.$entry->assignedGuard->employee_number : 'Deleted guard; ID reserved') : ($entry->checkpoint ? $entry->checkpoint->name.' / '.$entry->checkpoint->code : 'Deleted checkpoint; ID reserved') }}</p>
                                        @endif
                                    </li>
                                @empty
                                    <li class="p-4 text-sm text-zinc-500 dark:text-zinc-400" role="status">{{ $tab === 'handover' ? 'No guard handovers yet. Record an interval before accepting events.' : 'No IDs mapped yet. Add a verified external ID; do not use a fingerprint slot number without confirmation.' }}</li>
                                @endforelse
                            </ul>
                        </div>
                        <x-pagination :paginator="$entries" />
                    @else
                        <div class="card-surface p-5">
                            <h2 class="font-semibold">Choose a reader</h2>
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Select a registered reader to review its handovers, identity mappings and settings.</p>
                        </div>
                    @endif
                </section>
            </div>
        @endif
    </x-page-shell>
    @if ($showForm && $ready)
        @include('livewire.patrols.partials.jwm-reader-form')
    @endif
</div>
