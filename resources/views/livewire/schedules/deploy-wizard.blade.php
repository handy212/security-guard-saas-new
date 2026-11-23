<div>
    <x-page-shell :embedded="$embedded" title="Deploy to site" description="Guided flow: site → shift → guard → kit → confirm.">
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('schedules.deployment-sheet', ['date' => $date]) }}">Deployment sheet</x-button>
            <x-button variant="secondary" href="{{ route('schedules.index', ['date' => $date]) }}">Day roster</x-button>
        </x-slot:actions>

        <x-flash-status />

        <x-sub-sidebar-layout>
            @if (! $embedded)
                <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>
            @endif

            <div class="space-y-3.5" x-data x-effect="$wire.step; $nextTick(() => $el.querySelector('[data-deploy-heading]')?.focus())">
                <nav class="flex flex-wrap gap-1.5" aria-label="Deploy steps">
                    @foreach ($steps as $n => $label)
                        <div @if ($step === $n) aria-current="step" @endif @class([
                            'status-chip',
                            'border-accent-300 bg-accent-50 text-accent-800 dark:border-accent-700 dark:bg-accent-950/40 dark:text-accent-200' => $step === $n,
                            'status-chip-neutral opacity-70' => $step !== $n && $step < $n,
                            'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200' => $step > $n,
                        ])>
                            <span class="tabular-nums font-semibold">{{ $n }}</span> {{ $label }}
                        </div>
                    @endforeach
                </nav>
                <p wire:loading.delay role="status" aria-live="polite" class="text-sm text-zinc-500">Updating deployment...</p>

                @if ($step === 1)
                    <section class="card-surface overflow-hidden">
                        <div class="card-header">
                            <div>
                                <h2 class="card-header-title" tabindex="-1" data-deploy-heading>Where are they going?</h2>
                                <p class="card-header-meta">Pick a client, site, date, and optional post.</p>
                            </div>
                        </div>
                        @if ($canResumeDraft)
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200 px-4 py-2.5 dark:border-zinc-800">
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">You have an unfinished deployment for this date.</p>
                                <x-button size="sm" variant="secondary" wire:click="resumeDraft">Resume draft</x-button>
                            </div>
                        @endif
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
                                <h2 class="card-header-title" tabindex="-1" data-deploy-heading>Which shift?</h2>
                                <p class="card-header-meta">Use an existing open shift or create one for this deployment.</p>
                            </div>
                        </div>
                        <div class="space-y-4 p-4">
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" wire:click="$set('shift_mode', 'existing')" aria-pressed="{{ $shift_mode === 'existing' ? 'true' : 'false' }}" @class(['status-chip', $shift_mode === 'existing' ? 'border-accent-300 bg-accent-50 text-accent-800 dark:border-accent-700 dark:bg-accent-950/40 dark:text-accent-200' : 'status-chip-neutral'])>Existing shift</button>
                                <button type="button" wire:click="$set('shift_mode', 'new')" aria-pressed="{{ $shift_mode === 'new' ? 'true' : 'false' }}" @class(['status-chip', $shift_mode === 'new' ? 'border-accent-300 bg-accent-50 text-accent-800 dark:border-accent-700 dark:bg-accent-950/40 dark:text-accent-200' : 'status-chip-neutral'])>Create new shift</button>
                            </div>

                            @if ($shift_mode === 'existing')
                                <x-select wire:model.live="shift_id" label="Shift" required>
                                    <option value="">Select shift</option>
                                    @foreach ($shifts as $shift)
                                        @php
                                            $staffed = $shift->activeAssignmentsCount();
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
                                    <p role="status" class="text-xs text-amber-700 dark:text-amber-400">No shifts need guards for this site, post, and date. Choose another date or post, or create a new shift.</p>
                                @endif
                            @else
                                <div class="grid gap-4 md:grid-cols-2">
                                    <x-input wire:model.live="title" label="Shift title" required class="md:col-span-2" />
                                    <x-input wire:model.live="starts_at" type="datetime-local" label="Starts" required />
                                    <x-input wire:model.live="ends_at" type="datetime-local" label="Ends" required />
                                    <x-input wire:model.live="required_guards" type="number" min="1" label="Required guards" />
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
                                <h2 class="card-header-title" tabindex="-1" data-deploy-heading>Who is deploying?</h2>
                                <p class="card-header-meta">Only verified active guards are listed. Availability, leave, and overlapping shifts are checked before continuing.</p>
                            </div>
                        </div>
                        <div class="space-y-4 p-4">
                            <x-select wire:model.live="guard_id" label="Guard" required>
                                <option value="">Select guard</option>
                                @foreach ($guards as $guard)
                                    <option value="{{ $guard->id }}">
                                        {{ $guard->full_name }}
                                        @if ($guard->duty_type) · {{ $guard->duty_type }} @endif
                                        @if ($guard->employee_number) · {{ $guard->employee_number }} @endif
                                    </option>
                                @endforeach
                            </x-select>
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
                                <h2 class="card-header-title" tabindex="-1" data-deploy-heading>Issue kit (optional)</h2>
                                <p class="card-header-meta">Selected kit is issued immediately when you deploy, even for a future or pending assignment. Skip if handover will happen later.</p>
                            </div>
                        </div>
                        <div class="space-y-4 p-4">
                            @if ($kitGrouped->isEmpty())
                                <x-empty-state class="scheduler-empty-state"
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
                                                        aria-pressed="{{ $checked ? 'true' : 'false' }}"
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
                                <h2 class="card-header-title" tabindex="-1" data-deploy-heading>Confirm deployment</h2>
                                <p class="card-header-meta">Review and deploy. Confirmation marks the assignment ready for the field.</p>
                            </div>
                        </div>
                        <div class="space-y-4 p-4">
                            <dl class="grid gap-2 text-sm md:grid-cols-2">
                                <div class="meta-tile">
                                    <dt class="meta-tile-label">Site</dt>
                                    <dd class="meta-tile-value">{{ $shift_mode === 'existing' ? $selectedShift?->site?->name : $sites->firstWhere('id', (int) $site_id)?->name }}</dd>
                                </div>
                                <div class="meta-tile">
                                    <dt class="meta-tile-label">Post</dt>
                                    <dd class="meta-tile-value">{{ ($shift_mode === 'existing' ? $selectedShift?->sitePost?->name : $posts->firstWhere('id', (int) $site_post_id)?->name) ?? 'Site-wide' }}</dd>
                                </div>
                                <div class="meta-tile">
                                    <dt class="meta-tile-label">Shift</dt>
                                    <dd class="meta-tile-value">
                                        @if ($shift_mode === 'existing')
                                            {{ $selectedShift?->title ?? 'No shift selected' }}
                                            @if ($selectedShift)
                                                <div class="tabular-nums text-xs">{{ $selectedShift->starts_at->format('M j, Y H:i') }} – {{ $selectedShift->ends_at->format('M j, Y H:i') }}</div>
                                            @endif
                                        @else
                                            {{ $title }}
                                            <div class="tabular-nums text-xs">{{ \Carbon\Carbon::parse($starts_at)->format('M j, Y H:i') }} – {{ \Carbon\Carbon::parse($ends_at)->format('M j, Y H:i') }}</div>
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
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                @if ($shift_mode === 'existing' && $selectedShift)
                                    {{ $selectedShift->activeAssignmentsCount() }} of {{ $selectedShift->required_guards }} positions currently filled.
                                @else
                                    This creates a shift requiring {{ $required_guards }} {{ $required_guards === 1 ? 'guard' : 'guards' }} and assigns one guard.
                                @endif
                                @if (count($selectedAssetIds))
                                    Selected kit will be issued immediately.
                                @endif
                            </p>
                            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                <input type="checkbox" wire:model.live="confirm_now" class="rounded border-zinc-300 text-accent-600 focus:ring-accent-600/20" />
                                Confirm assignment now (skip pending confirmation)
                            </label>
                            @error('guard_id') <p class="form-error">{{ $message }}</p> @enderror
                            <div class="flex justify-between">
                                <x-button variant="secondary" wire:click="previousStep">Back</x-button>
                                <x-button wire:click="deploy" wire:target="deploy" loading-text="Creating assignment...">Deploy guard</x-button>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($step === 6 && $assignment)
                    <section class="card-surface overflow-hidden border-emerald-200/90 dark:border-emerald-900/50">
                        <div class="card-header border-emerald-100 bg-emerald-50/70 dark:border-emerald-900/40 dark:bg-emerald-950/30">
                            <div>
                                <h2 class="card-header-title text-emerald-900 dark:text-emerald-100" tabindex="-1" data-deploy-heading>
                                    {{ \App\Support\EnumHelper::is($assignment->status, 'confirmed') ? 'Assignment confirmed' : 'Awaiting confirmation' }}
                                </h2>
                                <p class="card-header-meta text-emerald-800/90 dark:text-emerald-300">{{ $assignment->assignedGuard?->full_name }} → {{ $assignment->shift?->site?->name }}</p>
                            </div>
                        </div>
                        <div class="space-y-4 p-4">
                            @php
                                $filled = $assignment->shift->activeAssignmentsCount();
                                $remaining = max(0, $assignment->shift->required_guards - $filled);
                            @endphp
                            <p role="status" class="text-sm">
                                {{ $filled }} of {{ $assignment->shift->required_guards }} positions filled.
                                @if ($remaining)
                                    {{ $remaining }} {{ $remaining === 1 ? 'position still needs a guard' : 'positions still need guards' }}.
                                @else
                                    All positions are filled.
                                @endif
                            </p>
                            @if (\App\Support\EnumHelper::is($assignment->status, 'assigned'))
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">Use the deployment sheet to review and confirm this assignment.</p>
                            @endif
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
