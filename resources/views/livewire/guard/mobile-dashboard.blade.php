<div data-guard-app class="space-y-4 field-workspace" wire:poll.60s="$refresh" x-data="{ feedback: '' }"
    x-on:field-feedback.window="feedback = $event.detail.message">
    <div x-show="tab === 'assignments'">
        <h1>{{ now()->hour < 12 ? 'Good morning' : (now()->hour < 18 ? 'Good afternoon' : 'Good evening') }}, {{ Str::before(auth()->user()->name, ' ') }}</h1>
        <p class="small">{{ now()->format('l, j F') }} · {{ $isOnDuty ? 'On duty' : 'Off duty' }}</p>
    </div>
    <div x-show="feedback" x-cloak role="alert" class="field-banner-danger">
        <span x-text="feedback"></span>
        <button type="button" class="ml-2 underline" x-on:click="feedback = ''">Dismiss</button>
    </div>
    @if($statusMessage)
        <div class="field-banner-success">{{ $statusMessage }}</div>
    @endif

    @error('action')
        <div class="field-banner-danger">{{ $message }}</div>
    @enderror

    @unless($hasGuardProfile)
        <div class="field-banner-warning">
            Your account is not linked to a guard profile. Contact your supervisor to use clock-in, patrol, and shift features.
        </div>
    @endunless

    <div id="shift" x-show="tab === 'shift'" x-cloak>
        <a class="btn inline-flex items-center" href="#assignments" x-on:click.prevent="showTab('assignments')">← Home</a>
        <h1 class="section">My shift</h1>
        <p class="small">Attendance and post orders</p>
    </div>
    <section id="assignments" x-show="tab === 'assignments' || tab === 'shift'" @class(['card', 'ok' => $isOnDuty])>
        @if ($selectedAssignment)
            @php
                $shift = $selectedAssignment->shift;
            @endphp
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="eyebrow">Current assignment</div>
                    <h2 class="big break-words">{{ $shift->site?->name ?? $shift->title }}</h2>
                    @if ($shift->site?->address)<p>⌖ {{ $shift->site->address }}</p>@endif
                    @if ($shift->sitePost)<p class="small">{{ $shift->sitePost->name }}</p>@endif
                </div>
                <span class="chip {{ $isOnDuty ? 'green' : 'amber' }}">{{ Str::headline(\App\Support\EnumHelper::value($selectedAssignment->status)) }}</span>
            </div>
            <div class="sep"></div>
            <div class="row between small">
                <span>{{ $shift->starts_at?->format('M j, H:i') }} – {{ $shift->ends_at?->format($shift->starts_at?->isSameDay($shift->ends_at) ? 'H:i' : 'M j, H:i') }}</span>
                <span>{{ $isOnDuty ? 'On duty' : 'Off duty' }}</span>
            </div>
            <div class="btnrow" x-show="tab === 'assignments'">
                <a class="btn text-center" href="#shift" x-on:click.prevent="showTab('shift')">View my shift</a>
            </div>
        @elseif ($assignments->isEmpty())
            <h2 class="field-panel-title">No shift scheduled</h2>
            <a href="#tools" x-on:click.prevent="openTool('field-open-shifts')" class="mt-2 inline-flex min-h-11 items-center text-sm font-semibold text-sky-300">View open shifts →</a>
        @endif

        @if ($assignments->count() > 1 || ($assignments->isNotEmpty() && ! $selectedAssignment))
            <label x-show="tab === 'shift' || @js(! $selectedAssignment)" class="mt-3 block text-xs font-medium text-zinc-400">
                Assignment
                <select wire:model.live="activeAssignmentId" class="field-input mt-1">
                    <option value="">Select a shift</option>
                    @foreach ($assignments as $assignment)
                        <option value="{{ $assignment->id }}">{{ $assignment->shift->site?->name ?? $assignment->shift->title }} · {{ $assignment->shift->starts_at?->format('M j, H:i') }}</option>
                    @endforeach
                </select>
            </label>
        @endif
        @if ($selectedAssignment)
            <div wire:key="shift-brief-{{ $selectedAssignment->id }}" x-show="tab === 'shift'" class="mt-3 space-y-2">
                <details wire:ignore.self class="field-tool rounded-lg border border-zinc-800">
                    <summary class="field-tool-title">Post orders <span aria-hidden="true">+</span></summary>
                    <div class="field-tool-content space-y-3">
                        @forelse ($postOrders as $order)
                            <article wire:key="guard-post-order-{{ $order->id }}" class="border-b border-zinc-800 pb-3 last:border-0 last:pb-0">
                                <p class="text-xs text-zinc-400">{{ $order->site_post_id === null ? 'Site-wide' : ($selectedAssignment->shift->sitePost?->name ?? 'Assigned post') }}</p>
                                <h3 class="font-semibold text-zinc-100">{{ $order->title }}</h3>
                                <p class="mt-1 whitespace-pre-wrap break-words text-sm text-zinc-300">{{ $order->instructions }}</p>
                            </article>
                        @empty
                            <p class="text-sm text-zinc-400">No active post orders for this shift.</p>
                        @endforelse
                    </div>
                </details>
                @if (! $selectedAssignment->confirmed_at && \App\Support\EnumHelper::value($selectedAssignment->status) === 'assigned')
                    <button type="button" wire:click="confirmMyShift" wire:loading.attr="disabled" wire:target="confirmMyShift"
                        class="field-btn-ghost !py-2">Confirm shift</button>
                @endif
            </div>
        @endif
    </section>

    @if ($standbyTeam && $dispatches->isEmpty())
        <section x-show="tab === 'assignments' || tab === 'dispatches'" class="card">
            <p class="eyebrow">{{ $standbyTeam->name }}</p>
            @if ($standbyTeam->standbyPoint?->region?->is_active)
                <h2 class="big">{{ $standbyTeam->standbyPoint->name }}</h2>
                <p class="small">Standby point · {{ $standbyTeam->standbyPoint->latitude }}, {{ $standbyTeam->standbyPoint->longitude }}</p>
            @else
                <p>Contact Control for your standby point.</p>
            @endif
        </section>
    @endif

    @if ($dispatches->isNotEmpty())
        <a href="#dispatches" x-show="tab !== 'dispatches'" x-cloak x-on:click.prevent="showTab('dispatches')"
            class="card urgent block">
            <span><strong class="block">{{ $dispatches->first()->dispatch_number ?? 'Active dispatch' }}</strong>
                <span class="text-xs">{{ $dispatches->first()->site?->name }} · {{ Str::headline($dispatches->first()->priority->value) }} priority</span></span>
            <span class="btn primary block mt-3 text-center">Open dispatch →</span>
        </a>
    @endif

    @if($hasGuardProfile)
    <section x-show="tab === 'shift'" class="grid grid-cols-1 gap-2" aria-label="Shift actions">
        @if (! $isOnDuty && $selectedAssignment)
        <button type="button"
            onclick="window.guardWithGeo(@this, 'clockIn', 'clock_in', (c, w) => ({ shift_assignment_id: w.activeAssignmentId, latitude: c.lat, longitude: c.lng }))"
            wire:loading.attr="disabled"
            wire:target="clockIn"
            class="btn success block"
            @disabled(! $selectedAssignment || $isOnDuty)>
            <span wire:loading.remove wire:target="clockIn">Clock In</span>
            <span wire:loading wire:target="clockIn">Working…</span>
        </button>
        @elseif ($isOnDuty)
        <button type="button"
            onclick="window.guardWithGeo(@this, 'clockOut', 'clock_out', (c, w) => ({ attendance_log_id: w.activeAttendanceId, latitude: c.lat, longitude: c.lng }))"
            wire:loading.attr="disabled"
            wire:target="clockOut"
            class="btn primary block"
            @disabled(! $isOnDuty)>
            <span wire:loading.remove wire:target="clockOut">Clock Out</span>
            <span wire:loading wire:target="clockOut">Working…</span>
        </button>
        @endif
        <button type="button"
            onclick="window.guardWithGeo(@this, 'updateLocation', 'location', (c) => ({ latitude: c.lat, longitude: c.lng }))"
            wire:loading.attr="disabled"
            wire:target="updateLocation"
            class="btn block">
            <span wire:loading.remove wire:target="updateLocation">Update GPS location</span>
            <span wire:loading wire:target="updateLocation">Updating…</span>
        </button>
    </section>
    <section
        id="sos" x-show="tab === 'sos'" x-cloak
        class="scroll-mt-20 rounded-lg border-2 border-red-500/50 bg-red-950/40 p-4"
        x-data="{ armed: false }"
    >
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold tracking-tight text-red-100">Emergency SOS</h2>
                <p class="mt-1 text-xs text-red-200/80">Send an emergency alert to Control. GPS is included when available.</p>
            </div>
            <span class="rounded-md bg-red-500/20 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-red-200">Critical</span>
        </div>

        @if ($latestSos)
            <div role="status" class="mt-3 rounded-lg border border-red-500/30 px-3 py-2 text-sm text-red-100">
                <strong>{{ match ($latestSos->status) {
                    'acknowledged' => 'Acknowledged by Control',
                    'resolved' => 'Resolved',
                    default => 'Server received · waiting for Control',
                } }}</strong>
                <p class="text-xs">SOS-{{ $latestSos->id }} · {{ ($latestSos->acknowledged_at ?? $latestSos->raised_at)?->format('M j, H:i') }}</p>
            </div>
        @endif
        <p class="mt-2 text-xs text-red-200/80">If delivery is unconfirmed, contact Control through your approved emergency channel.</p>

        <template x-if="! armed">
            <button
                type="button"
                @click="armed = true"
                class="field-btn-danger mt-4 text-base shadow-lg shadow-red-900/40"
            >
                Raise SOS alert
            </button>
        </template>

        <div x-show="armed" x-cloak class="mt-4 space-y-3">
            <p class="text-sm font-medium text-red-100">Confirm emergency — dispatch will be notified.</p>
            <div class="grid grid-cols-2 gap-3">
                <button type="button" @click="armed = false" class="field-btn-ghost !border-red-400/40 !text-red-100">Cancel</button>
                <button type="button"
                    @click="armed = false; window.guardWithGeo(@this, 'raiseSos')"
                    wire:loading.attr="disabled"
                    wire:target="raiseSos"
                    class="field-btn-danger">
                    <span wire:loading.remove wire:target="raiseSos">Confirm SOS</span>
                    <span wire:loading wire:target="raiseSos">Sending…</span>
                </button>
            </div>
        </div>
    </section>

    @if ($activePatrols->isNotEmpty() || $patrolRoutes->isNotEmpty())
        @php
            $homePatrol = $activePatrols->first();
            $homeRoute = $homePatrol?->route ?? $patrolRoutes->first();
        @endphp
        <section x-show="tab === 'assignments'" class="card">
            <div class="row between">
                <div>
                    <div class="eyebrow">Assigned patrol</div>
                    <strong>{{ $homeRoute?->name ?? 'Patrol' }}</strong>
                    <p class="small">{{ $homePatrol ? $homePatrol->verified_checkpoints.' checkpoints verified' : $homeRoute?->site?->name }}</p>
                </div>
                <span class="chip">{{ $homePatrol ? 'In progress' : 'Not started' }}</span>
            </div>
            <div class="bar"><div class="fill" style="width: {{ (int) ($homePatrol?->completion_percent ?? 0) }}%"></div></div>
            <div class="btnrow">
                <a href="#patrol" x-on:click.prevent="showTab('patrol')" class="btn text-center">View patrol</a>
                @if ($homePatrol)
                    <a href="#scan" x-on:click.prevent="showTab('scan')" class="btn primary text-center">Scan checkpoint</a>
                @endif
            </div>
        </section>
    @endif
    <section x-show="tab === 'assignments'" class="section">
        <h2>Quick actions</h2>
        <div class="tiles">
            <button type="button" class="tile" onclick="window.guardWithGeo(@this, 'updateLocation', 'location', (c) => ({ latitude: c.lat, longitude: c.lng }))">
                <span class="sym" aria-hidden="true">⌖</span><strong>Update location</strong><p>Send your current GPS position</p>
            </button>
            <a href="#tools" x-on:click.prevent="openTool('field-passdown')" class="tile"><span class="sym" aria-hidden="true">≡</span><strong>Passdown notes</strong><p>Prepare your handover</p></a>
            <a href="#notifications" x-on:click.prevent="showTab('notifications')" class="tile"><span class="sym" aria-hidden="true">♧</span><strong>Notifications</strong><p>Check operational updates</p></a>
            <a href="#shift" x-on:click.prevent="showTab('shift')" class="tile"><span class="sym" aria-hidden="true">▣</span><strong>Post orders</strong><p>Review your site instructions</p></a>
        </div>
    </section>

    <div x-show="tab === 'patrol'" x-cloak>
        <h1>Patrol</h1>
        <p class="small">Follow your route and verify checkpoints</p>
    </div>
    <section id="patrol" x-show="tab === 'patrol'" x-cloak class="card">
        <div class="mb-3 flex items-center justify-between gap-2">
            <div>
                <h2 class="eyebrow">Current route</h2>
            </div>
            @if($isOnDuty)
                <span class="text-xs font-medium text-emerald-400">On shift</span>
            @endif
        </div>

        @if($activePatrols->isNotEmpty())
            <div class="mb-3 space-y-2">
                <div class="text-[10px] font-semibold uppercase tracking-wide text-zinc-500">Active sessions</div>
                @foreach($activePatrols as $patrol)
                    <button type="button" wire:click="$set('patrolSessionId', {{ $patrol->id }})"
                        @class([
                            'w-full rounded-md border px-3 py-2 text-left text-sm transition',
                            'border-accent-500/70 bg-accent-500/10 text-zinc-100' => $patrolSessionId === $patrol->id,
                            'border-zinc-700 text-zinc-200 hover:border-zinc-600' => $patrolSessionId !== $patrol->id,
                        ])>
                        <span class="block big">{{ $patrol->route?->name ?? 'Patrol' }}</span>
                        <span class="small muted">{{ $patrol->verified_checkpoints }} checkpoints verified</span>
                        <progress class="bar block w-full accent-emerald-500" max="100" value="{{ $patrol->completion_percent ?? 0 }}" aria-label="Patrol progress"></progress>
                    </button>
                @endforeach
            </div>
        @endif

        @if($patrolRoutes->isNotEmpty())
            <div>
                <div class="mb-1.5 text-[10px] font-semibold uppercase tracking-wide text-zinc-500">Start new patrol</div>
                <div class="flex flex-wrap gap-2">
                    @foreach($patrolRoutes as $route)
                        <button type="button" wire:click="startPatrol({{ $route->id }})"
                            class="rounded-md border border-zinc-700 px-3 py-1.5 text-xs font-medium text-zinc-200 transition hover:border-accent-500 hover:text-accent-300">
                            {{ $route->name }}
                        </button>
                    @endforeach
                </div>
            </div>
        @elseif($assignments->isEmpty())
            <p class="text-sm text-zinc-400">You need a scheduled shift to start a patrol.</p>
        @elseif($activePatrols->isEmpty())
            <p class="text-sm text-zinc-400">No patrol routes available for your site.</p>
        @endif
    </section>

    <div x-show="tab === 'scan'" x-cloak>
        <h1>Checkpoint scan</h1>
        <p class="small">QR, NFC or manual checkpoint entry</p>
    </div>
    <section id="scan" x-show="tab === 'scan'" x-cloak class="card">
        @if($activePatrols->isEmpty())
            <p>No active patrol. <a href="#patrol" x-on:click.prevent="showTab('patrol')">Go to Patrol</a></p>
        @endif
        <div class="mb-3 flex items-start justify-between gap-2">
            <div>
                <h2 class="eyebrow">Active patrol</h2>
            </div>
            <div class="flex shrink-0 gap-2">
                <button type="button" wire:click="toggleNfcScanner" @disabled($activePatrols->isEmpty()) class="rounded-md border border-zinc-700 px-2.5 py-1 text-xs font-semibold text-zinc-200">
                    {{ $showNfcScanner ? 'Stop NFC' : 'Scan NFC' }}
                </button>
                <button type="button" wire:click="toggleScanner" @disabled($activePatrols->isEmpty()) class="rounded-md bg-accent-600 px-2.5 py-1 text-xs font-semibold text-white">
                    {{ $showScanner ? 'Close camera' : 'Scan QR' }}
                </button>
            </div>
        </div>

        <p wire:show="showNfcScanner" class="mb-3 text-xs text-zinc-400">Hold device near NFC tag…</p>
        <div wire:show="showScanner" class="mb-3 overflow-hidden rounded-md border border-zinc-700">
            <div id="qr-reader" wire:ignore></div>
        </div>

        @script
        <script>
            $wire.$watch('showNfcScanner', async (show) => {
                if (!show) {
                    await window.stopNfcScanner();
                    return;
                }

                try {
                    await window.startNfcScanner((code) => {
                        $wire.dispatch('nfc-scanned', { code });
                        $wire.$set('showNfcScanner', false);
                    });
                } catch (error) {
                    if (!$wire.showNfcScanner) return;
                    $wire.$set('statusMessage', error.message || 'NFC scan unavailable.', false);
                    $wire.$set('showNfcScanner', false);
                }
            });

            let cameraOperation = Promise.resolve();
            $wire.$watch('showScanner', (show) => {
                cameraOperation = cameraOperation.then(async () => {
                    if (!show) {
                        await window.stopQrScanner();
                        return;
                    }
                    if (!$wire.showScanner) return;

                    try {
                        await window.startQrScanner('qr-reader', (code) => {
                            if ($wire.showScanner) $wire.dispatch('qr-scanned', { code });
                        });
                    } catch (error) {
                        if (!$wire.showScanner) return;
                        $wire.$set('statusMessage', error.message || 'Camera scan unavailable.', false);
                        $wire.$set('showScanner', false);
                    }
                });
            });

            window.addEventListener('field-tab-changed', ({ detail }) => {
                if (detail.tab === 'scan') return;
                if ($wire.showScanner) $wire.$set('showScanner', false);
                if ($wire.showNfcScanner) $wire.$set('showNfcScanner', false);
            });
        </script>
        @endscript

        <select wire:model.live="patrolSessionId" aria-label="Active patrol" @disabled($activePatrols->isEmpty()) class="field-input mb-2">
            <option value="">Select patrol session</option>
            @foreach($activePatrols as $patrol)
                <option value="{{ $patrol->id }}">#{{ $patrol->id }} — {{ $patrol->route?->name }}</option>
            @endforeach
        </select>
        @error('patrolSessionId') <p role="alert">{{ $message }}</p> @enderror
        <input wire:model.live="checkpointCode" aria-label="Checkpoint code" @disabled($activePatrols->isEmpty()) type="text" placeholder="QR / NFC checkpoint code" class="field-input mb-2">
        @error('checkpointCode') <p role="alert">{{ $message }}</p> @enderror
        <button type="button"
            onclick="window.guardWithGeo(@this, 'scanCheckpoint', 'checkpoint_scan', (c, w) => ({ patrol_session_id: w.patrolSessionId, checkpoint_code: w.checkpointCode, latitude: c.lat, longitude: c.lng }))"
            class="field-btn-light" @disabled(! $activePatrols->contains('id', $patrolSessionId) || blank($checkpointCode))>Submit scan</button>
    </section>

    <div id="dispatches" x-show="tab === 'dispatches'" x-cloak x-data="{ dispatchTab: 'all', selectedDispatch: null }" class="space-y-3">
        <div x-show="selectedDispatch === null">
            <h1>Dispatch</h1>
            <p class="small">Assigned alarms and field responses</p>
        </div>
        <button class="btn" type="button" x-show="selectedDispatch !== null" x-on:click="selectedDispatch = null">← Back to dispatches</button>
        <div role="group" aria-label="Dispatch status" class="tabrow" x-show="selectedDispatch === null">
            @foreach (['all' => 'All', 'assigned' => 'Assigned', 'active' => 'Active', 'completed' => 'Completed'] as $key => $label)
                <button type="button" x-on:click="dispatchTab = @js($key)" x-bind:aria-pressed="dispatchTab === @js($key)"
                    x-bind:class="{ selected: dispatchTab === @js($key) }">{{ $label }}</button>
            @endforeach
        </div>
    @if($dispatches->isNotEmpty())
        <section x-show="dispatchTab !== 'completed'">
            @unless ($dispatches->contains('status', \App\Enums\DispatchStatus::ASSIGNED))
                <p x-show="selectedDispatch === null && dispatchTab === 'assigned'" class="empty">No assigned dispatches.</p>
            @endunless
            @unless ($dispatches->contains(fn ($item) => $item->status !== \App\Enums\DispatchStatus::ASSIGNED))
                <p x-show="selectedDispatch === null && dispatchTab === 'active'" class="empty">No dispatches in progress.</p>
            @endunless
            @foreach($dispatches as $dispatch)
                <div wire:key="dispatch-{{ $dispatch->id }}"
                    x-show="selectedDispatch === {{ $dispatch->id }} || (selectedDispatch === null && (dispatchTab === 'all' || dispatchTab === '{{ $dispatch->status === \App\Enums\DispatchStatus::ASSIGNED ? 'assigned' : 'active' }}'))">
                    <button type="button" x-show="selectedDispatch === null" x-on:click="selectedDispatch = {{ $dispatch->id }}"
                        @class(['card block w-full text-left', 'urgent' => $dispatch->priority->value === 'critical'])>
                        <span class="row between">
                            <strong>{{ $dispatch->dispatch_number }}</strong>
                            <span class="chip {{ $dispatch->priority->value === 'critical' ? 'red' : '' }}">{{ Str::headline($dispatch->priority->value) }}</span>
                        </span>
                        <span class="small muted">{{ $dispatch->site?->name ?? $dispatch->incident_location }} · {{ $dispatch->status->label() }}</span>
                    </button>
                    <div x-show="selectedDispatch === {{ $dispatch->id }}">
                    <div class="section">
                        <h1>{{ $dispatch->dispatch_number }}</h1>
                        <p class="small">{{ $dispatch->site?->name ?? $dispatch->incident_location }}</p>
                    </div>
                    <div @class(['card', 'urgent' => $dispatch->priority->value === 'critical'])>
                    <div class="row between">
                        <span class="chip {{ $dispatch->priority->value === 'critical' ? 'red' : '' }}">{{ Str::headline($dispatch->priority->value) }}</span>
                        <span class="chip amber">{{ $dispatch->status->label() }}</span>
                    </div>
                    <p class="small mt-2">{{ $dispatch->incident_location ?? $dispatch->site?->address }}</p>
                    @if ($dispatch->patriot_job_id !== null)
                        @if ($dispatch->latitude !== null && $dispatch->longitude !== null)
                            <p class="mt-1 text-xs text-zinc-300">{{ $dispatch->latitude }}, {{ $dispatch->longitude }}</p>
                        @endif
                        @if ($dispatch->patriot_payload['instructions'] ?? null)
                            <p class="mt-1 whitespace-pre-wrap text-xs text-zinc-300">{{ $dispatch->patriot_payload['instructions'] }}</p>
                        @endif
                    @endif
                    @if ($dispatch->patriot_pending_action)
                        <p class="text-xs text-amber-200">{{ $dispatch->patriot_writeback_error ? 'Patriot update needs Control’s attention.' : 'Waiting for Patriot confirmation.' }}</p>
                    @endif
                    @error('patriotDecision') <p class="text-xs text-red-300">{{ $message }}</p> @enderror
                    @if ($dispatch->dispatchTeam)
                        <p class="mt-1 text-xs text-zinc-300">{{ $dispatch->dispatchTeam->name }} · Standby: {{ $dispatch->dispatchTeam->standbyPoint?->name ?? 'Contact Control' }}</p>
                        @if ($point = $dispatch->dispatchTeam->standbyPoint)
                            <p class="text-xs text-zinc-300">Standby coordinates {{ $point->latitude }}, {{ $point->longitude }}</p>
                        @endif
                    @endif
                    @if ($dispatch->awaitingStationReturn())
                        <p class="mt-1 text-xs text-amber-200">Returning to station</p>
                        @if ($dispatch->guard_id === auth()->user()->guardProfile?->id)
                            <button type="button" wire:click="confirmDispatchReturn({{ $dispatch->id }})" class="field-btn-warning mt-2 !py-2 text-xs">Confirm back at station</button>
                        @endif
                    @elseif ($dispatch->patriot_pending_action)
                        {{-- The response is already queued. --}}
                    @elseif ($dispatch->patriot_job_id && $dispatch->guard_id === auth()->user()->guardProfile?->id && $dispatch->status === \App\Enums\DispatchStatus::ASSIGNED && $dispatch->patriot_response_state === null)
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button type="button" wire:click="respondToPatriotDispatch({{ $dispatch->id }}, 'accept')" wire:loading.attr="disabled" class="field-btn-warning !py-2 text-xs">Accept dispatch</button>
                            <button type="button" wire:click="respondToPatriotDispatch({{ $dispatch->id }}, 'decline')" wire:confirm="Decline this dispatch?" wire:loading.attr="disabled" class="field-btn-light !py-2 text-xs">Decline dispatch</button>
                        </div>
                    @elseif ($dispatch->dispatchTeam && $dispatch->guard_id === auth()->user()->guardProfile?->id && $dispatch->status === \App\Enums\DispatchStatus::ASSIGNED && ! $dispatch->acknowledged_at)
                        <button type="button" wire:click="acknowledgeDispatch({{ $dispatch->id }})" class="field-btn-warning mt-2 !py-2 text-xs">Acknowledge dispatch</button>
                    @elseif (($dispatch->dispatchTeam || $dispatch->patriot_job_id) && $dispatch->guard_id === auth()->user()->guardProfile?->id && $dispatch->status === \App\Enums\DispatchStatus::ON_SCENE)
                        <form wire:submit="submitDispatchOutcome({{ $dispatch->id }})" class="mt-2 space-y-2">
                            <label class="block text-xs" for="dispatch-outcome-{{ $dispatch->id }}">Response outcome</label>
                            <textarea id="dispatch-outcome-{{ $dispatch->id }}" wire:model="dispatchOutcomes.{{ $dispatch->id }}" maxlength="2000" required class="w-full rounded-md bg-zinc-900 p-2"></textarea>
                            @error('dispatchOutcomes.'.$dispatch->id) <p class="text-xs text-red-300">{{ $message }}</p> @enderror
                            <button type="submit" class="field-btn-warning !py-2 text-xs">Submit outcome</button>
                        </form>
                    @elseif($dispatch->status->next() && $dispatch->guard_id === auth()->user()->guardProfile?->id)
                        <button type="button" wire:click="advanceDispatch({{ $dispatch->id }})" class="field-btn-warning mt-2 !py-2 text-xs">
                            Mark {{ strtolower($dispatch->status->next()->label()) }}
                        </button>
                    @endif
                    </div>
                    </div>
                </div>
            @endforeach
        </section>
    @else
        <section x-show="dispatchTab !== 'completed'" class="field-panel">
            <p class="field-panel-meta">No active dispatches.</p>
        </section>
    @endif
        <section x-show="dispatchTab === 'completed'" class="field-panel space-y-2">
            @forelse ($finishedDispatches as $job)
                <div class="border-b border-zinc-800 pb-2 last:border-0 last:pb-0">
                    <strong>{{ $job->dispatch_number }}</strong>
                    <p class="text-xs text-zinc-400">{{ $job->site?->name }} · {{ $job->status->label() }}</p>
                </div>
            @empty
                <p class="field-panel-meta">No completed dispatches.</p>
            @endforelse
        </section>
    </div>

    <div id="tools" x-show="tab === 'tools'" x-cloak class="space-y-3">
    <h1>Operations Hub</h1>
    <p class="small">Shift tools, reports and handover</p>
    <div class="card"><strong>{{ auth()->user()->name }}</strong><p class="small">{{ $isOnDuty ? 'On duty' : 'Off duty' }}</p></div>
    <h2>Shift and attendance</h2>
    <a href="#shift" x-on:click.prevent="showTab('shift')" class="item">
        <span class="symbol" aria-hidden="true">▣</span><div class="grow"><strong>My shift</strong><p>Attendance and post orders</p></div><span aria-hidden="true">›</span>
    </a>
    <details class="item field-tool">
        <summary class="field-tool-title">Request shift swap<span aria-hidden="true">+</span></summary>
        <div class="field-tool-content">
        <p class="field-panel-meta mb-3">Ask scheduling to release you. Optionally suggest a replacement.</p>
        <select wire:model.live="swapReplacementGuardId" class="field-input mb-2" @disabled(! $selectedAssignment)>
            <option value="">No replacement suggested</option>
            @foreach($colleagueGuards as $colleague)
                <option value="{{ $colleague->id }}">{{ $colleague->full_name }}</option>
            @endforeach
        </select>
        <textarea wire:model.live="swapReason" rows="2" placeholder="Reason for swap (optional)" class="field-input mb-2" @disabled(! $selectedAssignment)></textarea>
        <button type="button" wire:click="requestShiftSwap" class="field-btn-ghost" @disabled(! $selectedAssignment)>Submit swap request</button>
        @if($mySwaps->isNotEmpty())
            <div class="mt-3 space-y-1 border-t border-zinc-800 pt-3 text-xs text-zinc-400">
                @foreach($mySwaps->take(5) as $swap)
                    <div>{{ $swap->shiftAssignment?->shift?->title }} — {{ ucfirst(\App\Support\EnumHelper::value($swap->status)) }}</div>
                @endforeach
            </div>
        @endif
    </div>
    </details>

    <details id="field-open-shifts" wire:ignore.self class="item field-tool scroll-mt-32">
        <summary class="field-tool-title">Open shifts<span aria-hidden="true">+</span></summary>
        <div class="field-tool-content">
        <p class="field-panel-meta mb-3">Bid on shifts that need coverage</p>
        @forelse($openShifts as $shift)
            <div class="mb-2 rounded-md border border-zinc-800 bg-zinc-950/50 p-3 text-sm last:mb-0" wire:key="open-shift-{{ $shift->id }}">
                <div class="font-medium text-zinc-100">{{ $shift->title }}</div>
                <div class="text-xs tabular-nums text-zinc-400">{{ $shift->site?->name }} · {{ $shift->starts_at?->format('M j, H:i') }}</div>
                <div class="mt-1 text-xs font-medium tabular-nums text-amber-300">{{ $shift->activeAssignmentsCount() }}/{{ $shift->required_guards }} filled</div>
                <textarea wire:model.live="bidNotes.{{ $shift->id }}" rows="2" placeholder="Optional note for scheduling" class="field-input mt-2 !py-1.5 text-xs"></textarea>
                <button type="button" wire:click="bidOnShift({{ $shift->id }})" class="field-btn-success mt-2 !py-2 text-xs">Place bid</button>
            </div>
        @empty
            <p class="text-sm text-zinc-400">No open shifts right now.</p>
        @endforelse
        @if($myBids->isNotEmpty())
            <div class="mt-3 border-t border-zinc-800 pt-3">
                <div class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-zinc-500">Your bids</div>
                @foreach($myBids->take(5) as $bid)
                    <div class="text-xs text-zinc-400">{{ $bid->shift?->title }} — {{ ucfirst(\App\Support\EnumHelper::value($bid->status)) }}</div>
                @endforeach
            </div>
        @endif
    </div>
    </details>

    <h2 class="pt-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">Field reports and handover</h2>
    @if($reportTemplates->isNotEmpty())
        <details class="item field-tool" @if($activeReportTemplateId) open @endif>
            <summary class="field-tool-title">Custom reports<span aria-hidden="true">+</span></summary>
        <div class="field-tool-content">
            <p class="field-panel-meta mb-3">Fill and submit site report forms</p>
            <select wire:model.live="activeReportTemplateId" class="field-input mb-2">
                <option value="">Select report</option>
                @foreach($reportTemplates as $template)
                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                @endforeach
            </select>
            @php $template = $reportTemplates->firstWhere('id', $activeReportTemplateId); @endphp
            @if($template)
                @foreach($template?->fields ?? [] as $field)
                    <div class="mb-2">
                        <label class="mb-1 block text-xs text-zinc-400">{{ $field->label }}</label>
                        @if($field->field_type === 'textarea')
                            <textarea wire:model.live="reportData.{{ $field->id }}" class="field-input" rows="2"></textarea>
                        @else
                            <input wire:model.live="reportData.{{ $field->id }}" type="text" class="field-input">
                        @endif
                        @error('reportData.'.$field->id) <p role="alert">{{ $message }}</p> @enderror
                    </div>
                @endforeach
                <div class="flex gap-2">
                    <button type="button" wire:click="saveReportDraft" class="field-btn-ghost flex-1">Save draft</button>
                    <button type="button" wire:click="submitCustomReport" class="field-btn-success flex-1 !py-2.5">Submit</button>
                </div>
            @endif
        </div>
    </details>
    @endif

    <details id="field-passdown" wire:ignore.self class="item field-tool scroll-mt-32" @if($errors->has('passdownContent')) open @endif>
        <summary class="field-tool-title">Passdown<span aria-hidden="true">+</span></summary>
        <div class="field-tool-content">
        <p class="field-panel-meta mb-3">Handoff notes for the next guard</p>
        <textarea wire:model.live="passdownContent" rows="3" placeholder="Handoff notes for next guard..." class="field-input mb-2" @disabled(! $selectedAssignment)></textarea>
        @error('passdownContent') <p class="mb-2 text-xs text-red-400">{{ $message }}</p> @enderror
        <button type="button" wire:click="savePassdown" class="field-btn-light" @disabled(! $selectedAssignment)>Save passdown</button>
    </div>
    </details>
    </div>
    @endif

    <section id="notifications" x-show="tab === 'notifications'" x-cloak>
        <h1>Notifications</h1>
        <p class="small">Assignments and operational alerts</p>
        @forelse ($fieldNotifications as $notification)
            <article @class(['card', 'urgent' => $notification->read_at === null]) wire:key="field-notification-{{ $notification->id }}">
                <strong>{{ $notification->data['title'] ?? 'Notification' }}</strong>
                <p class="small">{{ $notification->data['body'] ?? $notification->data['message'] ?? '' }}</p>
                @if ($notification->read_at === null)
                    <button class="btn mt-2" wire:click="markFieldNotificationRead('{{ $notification->id }}')">Mark read</button>
                @endif
            </article>
        @empty
            <p class="empty">No notifications.</p>
        @endforelse
    </section>
</div>
