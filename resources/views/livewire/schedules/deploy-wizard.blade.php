<div>
    <x-page-shell title="Deploy to site" description="Guided flow: site → shift → guard → kit → confirm.">
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('schedules.deployment-sheet', ['date' => $date]) }}">Deployment sheet</x-button>
            <x-button variant="secondary" href="{{ route('schedules.index', ['date' => $date]) }}">Day roster</x-button>
        </x-slot:actions>

        <x-flash-status />

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>

            <div class="space-y-4">
                <nav class="flex flex-wrap gap-2" aria-label="Deploy steps">
                    @foreach ($steps as $n => $label)
                        <div @class([
                            'rounded-lg border px-3 py-1.5 text-xs font-medium',
                            'border-accent-300 bg-accent-50 text-accent-800 dark:border-accent-700 dark:bg-accent-950/40 dark:text-accent-200' => $step === $n,
                            'border-zinc-200 text-zinc-500 dark:border-zinc-700' => $step !== $n && $step < $n,
                            'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200' => $step > $n,
                        ])>
                            {{ $n }}. {{ $label }}
                        </div>
                    @endforeach
                </nav>

                @if ($step === 1)
                    <section class="card-surface space-y-4 p-4">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Where are they going?</h2>
                            <p class="text-xs text-zinc-500">Pick client, site, and optional post for {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}.</p>
                        </div>
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
                    </section>
                @endif

                @if ($step === 2)
                    <section class="card-surface space-y-4 p-4">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Which shift?</h2>
                            <p class="text-xs text-zinc-500">Use an existing open shift or create one for this deployment.</p>
                        </div>

                        <div class="flex gap-2">
                            <button type="button" wire:click="$set('shift_mode', 'existing')" @class(['rounded-lg border px-3 py-2 text-xs font-medium', 'border-accent-300 bg-accent-50' => $shift_mode === 'existing', 'border-zinc-200' => $shift_mode !== 'existing'])>Existing shift</button>
                            <button type="button" wire:click="$set('shift_mode', 'new')" @class(['rounded-lg border px-3 py-2 text-xs font-medium', 'border-accent-300 bg-accent-50' => $shift_mode === 'new', 'border-zinc-200' => $shift_mode !== 'new'])>Create new shift</button>
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
                                <p class="text-xs text-amber-700">No shifts on this site for this date. Create a new shift instead.</p>
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
                    </section>
                @endif

                @if ($step === 3)
                    <section class="card-surface space-y-4 p-4">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Who is deploying?</h2>
                            <p class="text-xs text-zinc-500">Only verified active guards are listed.</p>
                        </div>
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
                        @error('guard_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <div class="flex justify-between">
                            <x-button variant="secondary" wire:click="previousStep">Back</x-button>
                            <x-button wire:click="nextStep">Continue</x-button>
                        </div>
                    </section>
                @endif

                @if ($step === 4)
                    <section class="card-surface space-y-4 p-4">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Issue kit (optional)</h2>
                            <p class="text-xs text-zinc-500">Vehicles, motors, radios, and bodycams from Assets. Skip if none needed.</p>
                        </div>

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
                                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-500">{{ $category }}</p>
                                        <div class="grid gap-2 sm:grid-cols-2">
                                            @foreach ($assets as $asset)
                                                @php $checked = in_array($asset->id, array_map('intval', $selectedAssetIds), true); @endphp
                                                <button
                                                    type="button"
                                                    wire:click="toggleAsset({{ $asset->id }})"
                                                    @class([
                                                        'rounded-lg border px-3 py-2 text-left text-sm transition',
                                                        'border-accent-400 bg-accent-50 text-accent-900 dark:border-accent-600 dark:bg-accent-950/40 dark:text-accent-100' => $checked,
                                                        'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' => ! $checked,
                                                    ])
                                                >
                                                    <div class="font-medium">{{ $asset->name }}</div>
                                                    <div class="text-xs text-zinc-500">
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

                        @error('selectedAssetIds') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        @error('selectedAssetIds.*') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                        <div class="flex justify-between">
                            <x-button variant="secondary" wire:click="previousStep">Back</x-button>
                            <x-button wire:click="nextStep">{{ count($selectedAssetIds) ? 'Continue with kit' : 'Skip kit' }}</x-button>
                        </div>
                    </section>
                @endif

                @if ($step === 5)
                    <section class="card-surface space-y-4 p-4">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Confirm deployment</h2>
                            <p class="text-xs text-zinc-500">Review and deploy. Confirmation marks the assignment ready for the field.</p>
                        </div>
                        <dl class="grid gap-3 text-sm md:grid-cols-2">
                            <div>
                                <dt class="text-xs text-zinc-500">Site</dt>
                                <dd class="font-medium">{{ $sites->firstWhere('id', (int) $site_id)?->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-zinc-500">Post</dt>
                                <dd class="font-medium">{{ $posts->firstWhere('id', (int) $site_post_id)?->name ?? 'Site-wide' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-zinc-500">Shift</dt>
                                <dd class="font-medium">
                                    @if ($shift_mode === 'existing')
                                        {{ $shifts->firstWhere('id', (int) $shift_id)?->title ?? 'Selected shift' }}
                                    @else
                                        {{ $title }} · {{ \Carbon\Carbon::parse($starts_at)->format('H:i') }}–{{ \Carbon\Carbon::parse($ends_at)->format('H:i') }}
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-zinc-500">Guard</dt>
                                <dd class="font-medium">{{ $guards->firstWhere('id', (int) $guard_id)?->full_name }}</dd>
                            </div>
                            <div class="md:col-span-2">
                                <dt class="text-xs text-zinc-500">Kit</dt>
                                <dd class="font-medium">
                                    @if ($selectedLabels->isEmpty())
                                        None
                                    @else
                                        {{ $selectedLabels->join(', ') }}
                                    @endif
                                </dd>
                            </div>
                        </dl>
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model="confirm_now" class="rounded border-zinc-300" />
                            Confirm assignment now (skip pending confirmation)
                        </label>
                        @error('guard_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <div class="flex justify-between">
                            <x-button variant="secondary" wire:click="previousStep">Back</x-button>
                            <x-button wire:click="deploy">Deploy guard</x-button>
                        </div>
                    </section>
                @endif

                @if ($step === 6 && $assignment)
                    <section class="card-surface space-y-4 p-4">
                        <div>
                            <h2 class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">Deployed</h2>
                            <p class="text-xs text-zinc-500">{{ $assignment->assignedGuard?->full_name }} → {{ $assignment->shift?->site?->name }}</p>
                            @if ($assignment->equipmentAssignments->isNotEmpty())
                                <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-400">
                                    Kit:
                                    {{ $assignment->equipmentAssignments->map(fn ($e) => $e->asset?->displayLabel())->filter()->join(', ') }}
                                </p>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-button href="{{ route('schedules.deployment-sheet', ['date' => $date]) }}">Open deployment sheet</x-button>
                            <x-button variant="secondary" href="{{ route('schedules.index', ['date' => $date]) }}">Day roster</x-button>
                            <x-button variant="secondary" wire:click="resetWizard">Deploy another</x-button>
                        </div>
                    </section>
                @endif
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
