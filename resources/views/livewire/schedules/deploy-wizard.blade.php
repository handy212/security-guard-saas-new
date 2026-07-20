<div>
    <x-page-shell title="Deploy to site" description="Guided flow: site → shift → guard → kit → confirm.">
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('schedules.deployment-sheet', ['date' => $date]) }}">Deployment sheet</x-button>
            <x-button variant="secondary" href="{{ route('schedules.index', ['date' => $date]) }}">Day roster</x-button>
        </x-slot:actions>

        <x-flash-status />

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>

            <div class="space-y-3.5">
                <nav class="flex flex-wrap gap-1.5" aria-label="Deploy steps">
                    @foreach ($steps as $n => $label)
                        <div @class([
                            'status-chip',
                            'border-accent-300 bg-accent-50 text-accent-800 dark:border-accent-700 dark:bg-accent-950/40 dark:text-accent-200' => $step === $n,
                            'status-chip-neutral opacity-70' => $step !== $n && $step < $n,
                            'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200' => $step > $n,
                        ])>
                            <span class="tabular-nums font-semibold">{{ $n }}</span> {{ $label }}
                        </div>
                    @endforeach
                </nav>

                @if ($step === 1)
                    <section class="card-surface overflow-hidden">
                        <div class="card-header">
                            <div>
                                <h2 class="card-header-title">Where are they going?</h2>
                                <p class="card-header-meta">Pick client, site, and optional post for {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}.</p>
                            </div>
                        </div>
                        <div class="space-y-4 p-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <x-input wire:model.live="date" type="date" label="Deployment date" />
                                <x-select wire:model.live="client_account_id" label="Client" required>
                                    <option value="">Select client</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                </x-select>
                                <x-select wire:model.live="site_id" label="Site" required>
                                    <option value="">Select site</option>
                                    @foreach ($sites as $site)
                                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                                    @endforeach
                                </x-select>
                                <x-select wire:model.live="site_post_id" label="Post (optional)">
                                    <option value="">Any / site-wide</option>
                                    @foreach ($posts as $post)
                                        <option value="{{ $post->id }}">{{ $post->name }}</option>
                                    @endforeach
                                </x-select>
                            </div>
                            <div class="flex justify-end">
                                <x-button wire:click="nextStep">Continue</x-button>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($step === 2)
                    <section class="card-surface overflow-hidden">
                        <div class="card-header">
                            <div>
                                <h2 class="card-header-title">Which shift?</h2>
                                <p class="card-header-meta">Use an existing open shift or create one for this deployment.</p>
                            </div>
                        </div>
                        <div class="space-y-4 p-4">
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" wire:click="$set('shift_mode', 'existing')" @class(['status-chip', $shift_mode === 'existing' ? 'border-accent-300 bg-accent-50 text-accent-800 dark:border-accent-700 dark:bg-accent-950/40 dark:text-accent-200' : 'status-chip-neutral'])>Existing shift</button>
                                <button type="button" wire:click="$set('shift_mode', 'new')" @class(['status-chip', $shift_mode === 'new' ? 'border-accent-300 bg-accent-50 text-accent-800 dark:border-accent-700 dark:bg-accent-950/40 dark:text-accent-200' : 'status-chip-neutral'])>Create new shift</button>
                            </div>

                            @if ($shift_mode === 'existing')
                                <x-select wire:model="shift_id" label="Shift" required>
                                    <option value="">Select shift</option>
                                    @foreach ($shifts as $shift)
                                        @php
                                            $staffed = $shift->assignments->filter(fn ($a) => ! in_array(\App\Support\EnumHelper::value($a->status), ['cancelled', 'no_show'], true))->count();
                                        @endphp
                                        <option value="{{ $shift->id }}">
                                            {{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}
                                            · {{ $shift->title }}
                                            · {{ $staffed }}/{{ $shift->required_guards }}
                                            @if ($shift->sitePost) · {{ $shift->sitePost->name }} @endif
                                        </option>
                                    @endforeach
                                </x-select>
                                @if ($shifts->isEmpty())
                                    <p class="text-xs text-amber-700 dark:text-amber-400">No shifts on this site for this date. Create a new shift instead.</p>
                                @endif
                            @else
                                <div class="grid gap-4 md:grid-cols-2">
                                    <x-input wire:model="title" label="Shift title" required class="md:col-span-2" />
                                    <x-input wire:model="starts_at" type="datetime-local" label="Starts" required />
                                    <x-input wire:model="ends_at" type="datetime-local" label="Ends" required />
                                    <x-input wire:model="required_guards" type="number" min="1" label="Required guards" />
                                </div>
                            @endif

                            <div class="flex justify-between">
                                <x-button variant="secondary" wire:click="previousStep">Back</x-button>
                                <x-button wire:click="nextStep">Continue</x-button>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($step === 3)
                    <section class="card-surface overflow-hidden">
                        <div class="card-header">
                            <div>
                                <h2 class="card-header-title">Who is deploying?</h2>
                                <p class="card-header-meta">Only verified active guards are listed.</p>
                            </div>
                        </div>
                        <div class="space-y-4 p-4">
                            <x-select wire:model="guard_id" label="Guard" required>
                                <option value="">Select guard</option>
                                @foreach ($guards as $guard)
                                    <option value="{{ $guard->id }}">
                                        {{ $guard->full_name }}
                                        @if ($guard->duty_type) · {{ $guard->duty_type }} @endif
                                        @if ($guard->employee_number) · {{ $guard->employee_number }} @endif
                                    </option>
                                @endforeach
                            </x-select>
                            @error('guard_id') <p class="form-error">{{ $message }}</p> @enderror
                            <div class="flex justify-between">
                                <x-button variant="secondary" wire:click="previousStep">Back</x-button>
                                <x-button wire:click="nextStep">Continue</x-button>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($step === 4)
                    <section class="card-surface overflow-hidden">
                        <div class="card-header">
                            <div>
                                <h2 class="card-header-title">Issue kit (optional)</h2>
                                <p class="card-header-meta">Vehicles, motors, radios, and bodycams from Assets. Skip if none needed.</p>
                            </div>
                        </div>
                        <div class="space-y-4 p-4">
                            @if ($kitGrouped->isEmpty())
                                <x-empty-state
                                    compact
                                    title="No available kit assets"
                                    description="Add Vehicles, Motors, Radios, or Bodycams under Assets — or create fleet units (they sync into Assets)."
                                >
                                    <x-slot:actions>
                                        <x-button size="sm" variant="secondary" href="{{ route('assets.index') }}">Open Assets</x-button>
                                        <x-button size="sm" variant="secondary" href="{{ route('patrols.fleet') }}">Fleet</x-button>
                                    </x-slot:actions>
                                </x-empty-state>
                            @else
                                <div class="space-y-4">
                                    @foreach ($kitGrouped as $category => $assets)
                                        <div>
                                            <p class="meta-tile-label mb-2">{{ $category }}</p>
                                            <div class="grid gap-2 sm:grid-cols-2">
                                                @foreach ($assets as $asset)
                                                    @php $checked = in_array($asset->id, array_map('intval', $selectedAssetIds), true); @endphp
                                                    <button
                                                        type="button"
                                                        wire:click="toggleAsset({{ $asset->id }})"
                                                        @class([
                                                            'rounded-md border px-3 py-2.5 text-left text-sm transition',
                                                            'border-accent-400 bg-accent-50 text-accent-900 dark:border-accent-600 dark:bg-accent-950/40 dark:text-accent-100' => $checked,
                                                            'border-zinc-200/90 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' => ! $checked,
                                                        ])
                                                    >
                                                        <div class="font-medium">{{ $asset->name }}</div>
                                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                            @if ($asset->asset_tag){{ $asset->asset_tag }} · @endif
                                                            {{ $asset->serial_number ?: 'Available' }}
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @error('selectedAssetIds') <p class="form-error">{{ $message }}</p> @enderror
                            @error('selectedAssetIds.*') <p class="form-error">{{ $message }}</p> @enderror

                            <div class="flex justify-between">
                                <x-button variant="secondary" wire:click="previousStep">Back</x-button>
                                <x-button wire:click="nextStep">{{ count($selectedAssetIds) ? 'Continue with kit' : 'Skip kit' }}</x-button>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($step === 5)
                    <section class="card-surface overflow-hidden">
                        <div class="card-header">
                            <div>
                                <h2 class="card-header-title">Confirm deployment</h2>
                                <p class="card-header-meta">Review and deploy. Confirmation marks the assignment ready for the field.</p>
                            </div>
                        </div>
                        <div class="space-y-4 p-4">
                            <dl class="grid gap-2 text-sm md:grid-cols-2">
                                <div class="meta-tile">
                                    <dt class="meta-tile-label">Site</dt>
                                    <dd class="meta-tile-value">{{ $sites->firstWhere('id', (int) $site_id)?->name }}</dd>
                                </div>
                                <div class="meta-tile">
                                    <dt class="meta-tile-label">Post</dt>
                                    <dd class="meta-tile-value">{{ $posts->firstWhere('id', (int) $site_post_id)?->name ?? 'Site-wide' }}</dd>
                                </div>
                                <div class="meta-tile">
                                    <dt class="meta-tile-label">Shift</dt>
                                    <dd class="meta-tile-value">
                                        @if ($shift_mode === 'existing')
                                            {{ $shifts->firstWhere('id', (int) $shift_id)?->title ?? 'Selected shift' }}
                                        @else
                                            {{ $title }} · <span class="tabular-nums">{{ \Carbon\Carbon::parse($starts_at)->format('H:i') }}–{{ \Carbon\Carbon::parse($ends_at)->format('H:i') }}</span>
                                        @endif
                                    </dd>
                                </div>
                                <div class="meta-tile">
                                    <dt class="meta-tile-label">Guard</dt>
                                    <dd class="meta-tile-value">{{ $guards->firstWhere('id', (int) $guard_id)?->full_name }}</dd>
                                </div>
                                <div class="meta-tile md:col-span-2">
                                    <dt class="meta-tile-label">Kit</dt>
                                    <dd class="meta-tile-value">
                                        @if ($selectedLabels->isEmpty())
                                            None
                                        @else
                                            {{ $selectedLabels->join(', ') }}
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                <input type="checkbox" wire:model="confirm_now" class="rounded border-zinc-300 text-accent-600 focus:ring-accent-600/20" />
                                Confirm assignment now (skip pending confirmation)
                            </label>
                            @error('guard_id') <p class="form-error">{{ $message }}</p> @enderror
                            <div class="flex justify-between">
                                <x-button variant="secondary" wire:click="previousStep">Back</x-button>
                                <x-button wire:click="deploy">Deploy guard</x-button>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($step === 6 && $assignment)
                    <section class="card-surface overflow-hidden border-emerald-200/90 dark:border-emerald-900/50">
                        <div class="card-header border-emerald-100 bg-emerald-50/70 dark:border-emerald-900/40 dark:bg-emerald-950/30">
                            <div>
                                <h2 class="card-header-title text-emerald-900 dark:text-emerald-100">Deployed</h2>
                                <p class="card-header-meta text-emerald-800/90 dark:text-emerald-300">{{ $assignment->assignedGuard?->full_name }} → {{ $assignment->shift?->site?->name }}</p>
                            </div>
                        </div>
                        <div class="space-y-4 p-4">
                            @if ($assignment->equipmentAssignments->isNotEmpty())
                                <div class="meta-tile">
                                    <dt class="meta-tile-label">Kit issued</dt>
                                    <dd class="meta-tile-value">
                                        {{ $assignment->equipmentAssignments->map(fn ($e) => $e->asset?->displayLabel())->filter()->join(', ') }}
                                    </dd>
                                </div>
                            @endif
                            <div class="flex flex-wrap gap-2">
                                <x-button href="{{ route('schedules.deployment-sheet', ['date' => $date]) }}">Open deployment sheet</x-button>
                                <x-button variant="secondary" href="{{ route('schedules.index', ['date' => $date]) }}">Day roster</x-button>
                                <x-button variant="secondary" wire:click="resetWizard">Deploy another</x-button>
                            </div>
                        </div>
                    </section>
                @endif
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
