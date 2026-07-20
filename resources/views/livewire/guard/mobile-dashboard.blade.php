<div class="space-y-4" wire:poll.60s="$refresh">
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

    @if($hasGuardProfile && $isOnDuty)
        <div class="field-banner-success font-medium">
            You are clocked in. Remember to clock out when your shift ends.
        </div>
    @endif

    <section id="assignments" class="field-panel">
        <h2 class="field-panel-title">Today's assignments</h2>
        <p class="field-panel-meta">Select a shift to clock in or submit passdown notes</p>
        @forelse($assignments as $assignment)
            <label @class([
                'field-choice',
                'field-choice-active' => $activeAssignmentId === $assignment->id,
            ])>
                <input type="radio" wire:model.live="activeAssignmentId" value="{{ $assignment->id }}" class="mt-1 accent-[var(--tenant-brand,#0f766e)]">
                <div class="min-w-0">
                    <div class="font-medium text-zinc-100">{{ $assignment->shift?->title ?? $assignment->shift?->site?->name }}</div>
                    <div class="text-xs tabular-nums text-zinc-400">
                        {{ $assignment->shift?->site?->name }}
                        · {{ $assignment->shift?->starts_at?->format('M j, H:i') }} – {{ $assignment->shift?->ends_at?->format('H:i') }}
                    </div>
                    <div class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-zinc-500">
                        {{ ucfirst(\App\Support\EnumHelper::value($assignment->status)) }}
                    </div>
                </div>
            </label>
        @empty
            <p class="mt-3 text-sm text-zinc-400">No shifts scheduled for today.</p>
        @endforelse
    </section>

    @if($hasGuardProfile)
    <section class="grid grid-cols-2 gap-3">
        <button type="button"
            onclick="window.guardWithGeo(@this, 'clockIn', 'clock_in', (c, w) => ({ shift_assignment_id: w.activeAssignmentId, latitude: c.lat, longitude: c.lng }))"
            wire:loading.attr="disabled"
            wire:target="clockIn"
            class="field-btn-success"
            @disabled(! $activeAssignmentId || $isOnDuty)>
            <span wire:loading.remove wire:target="clockIn">Clock In</span>
            <span wire:loading wire:target="clockIn">Working…</span>
        </button>
        <button type="button"
            onclick="window.guardWithGeo(@this, 'clockOut', 'clock_out', (c, w) => ({ attendance_log_id: w.activeAttendanceId, latitude: c.lat, longitude: c.lng }))"
            wire:loading.attr="disabled"
            wire:target="clockOut"
            class="field-btn-warning"
            @disabled(! $isOnDuty)>
            <span wire:loading.remove wire:target="clockOut">Clock Out</span>
            <span wire:loading wire:target="clockOut">Working…</span>
        </button>
        <button type="button"
            onclick="window.guardWithGeo(@this, 'updateLocation', 'location', (c) => ({ latitude: c.lat, longitude: c.lng }))"
            wire:loading.attr="disabled"
            wire:target="updateLocation"
            class="field-btn-primary col-span-2">
            <span wire:loading.remove wire:target="updateLocation">Update GPS location</span>
            <span wire:loading wire:target="updateLocation">Updating…</span>
        </button>
    </section>
    @if(! $activeAssignmentId)
        <p class="text-center text-xs text-zinc-500">Select a shift above to clock in or submit passdown notes.</p>
    @endif

    <section
        id="sos"
        class="scroll-mt-20 rounded-lg border-2 border-red-500/50 bg-red-950/40 p-4"
        x-data="{ armed: false }"
    >
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold tracking-tight text-red-100">Emergency SOS</h2>
                <p class="mt-1 text-xs text-red-200/80">Alerts dispatch immediately with your GPS position.</p>
            </div>
            <span class="rounded-md bg-red-500/20 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-red-200">Critical</span>
        </div>

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
                    @click="armed = false; window.guardWithGeo(@this, 'raiseSos', 'sos', (c) => ({ latitude: c.lat, longitude: c.lng, message: 'SOS (offline queued)' }))"
                    wire:loading.attr="disabled"
                    wire:target="raiseSos"
                    class="field-btn-danger">
                    <span wire:loading.remove wire:target="raiseSos">Confirm SOS</span>
                    <span wire:loading wire:target="raiseSos">Sending…</span>
                </button>
            </div>
        </div>
    </section>

    <section id="patrol" class="field-panel">
        <div class="mb-3 flex items-center justify-between gap-2">
            <div>
                <h2 class="field-panel-title">Patrol</h2>
                <p class="field-panel-meta">Start a route or resume an active session</p>
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
                        #{{ $patrol->id }} — {{ $patrol->route?->name }}
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
        @elseif($activePatrols->isEmpty())
            <p class="text-sm text-zinc-400">No patrol routes available for your site.</p>
        @endif
    </section>

    <section id="scan" class="field-panel">
        <div class="mb-3 flex items-start justify-between gap-2">
            <div>
                <h2 class="field-panel-title">Checkpoint scan</h2>
                <p class="field-panel-meta">QR, NFC, or manual code entry</p>
            </div>
            <div class="flex shrink-0 gap-2">
                <button type="button" wire:click="toggleNfcScanner" class="rounded-md border border-zinc-700 px-2.5 py-1 text-xs font-semibold text-zinc-200">
                    {{ $showNfcScanner ? 'Stop NFC' : 'Scan NFC' }}
                </button>
                <button type="button" wire:click="toggleScanner" class="rounded-md bg-accent-600 px-2.5 py-1 text-xs font-semibold text-white">
                    {{ $showScanner ? 'Close camera' : 'Scan QR' }}
                </button>
            </div>
        </div>

        @if($showNfcScanner)
            @script
            <script>
                $wire.$watch('showNfcScanner', async (show) => {
                    if (show) {
                        try {
                            await window.startNfcScanner((code) => {
                                $wire.dispatch('nfc-scanned', { code });
                                window.stopNfcScanner();
                                $wire.set('showNfcScanner', false);
                            });
                        } catch (e) {
                            $wire.set('statusMessage', e.message || 'NFC scan unavailable.');
                            $wire.set('showNfcScanner', false);
                        }
                    } else {
                        await window.stopNfcScanner?.();
                    }
                });
            </script>
            @endscript
            <p class="mb-3 text-xs text-zinc-400">Hold device near NFC tag…</p>
        @endif

        @if($showScanner)
            <div id="qr-reader" class="mb-3 overflow-hidden rounded-md border border-zinc-700" wire:ignore></div>
            @script
            <script>
                $wire.$watch('showScanner', async (show) => {
                    if (show) {
                        await window.startQrScanner('qr-reader', (code) => {
                            $wire.dispatch('qr-scanned', { code });
                            window.stopQrScanner();
                        });
                    } else {
                        await window.stopQrScanner();
                    }
                });
                if ($wire.showScanner) {
                    window.startQrScanner('qr-reader', (code) => {
                        $wire.dispatch('qr-scanned', { code });
                        window.stopQrScanner();
                    });
                }
            </script>
            @endscript
        @endif

        <select wire:model="patrolSessionId" class="field-input mb-2">
            <option value="">Select patrol session</option>
            @foreach($activePatrols as $patrol)
                <option value="{{ $patrol->id }}">#{{ $patrol->id }} — {{ $patrol->route?->name }}</option>
            @endforeach
        </select>
        <input wire:model="checkpointCode" type="text" placeholder="QR / NFC checkpoint code" class="field-input mb-2">
        <button type="button"
            onclick="window.guardWithGeo(@this, 'scanCheckpoint', 'checkpoint_scan', (c, w) => ({ patrol_session_id: w.patrolSessionId, checkpoint_code: w.checkpointCode, latitude: c.lat, longitude: c.lng }))"
            class="field-btn-light">Submit scan</button>
    </section>

    @if($dispatches->isNotEmpty())
        <section class="rounded-lg border border-amber-500/40 bg-amber-500/5 p-4">
            <h2 class="mb-2 text-sm font-semibold tracking-tight text-amber-200">Active dispatches</h2>
            @foreach($dispatches as $dispatch)
                <div class="mb-2 rounded-md border border-amber-500/30 bg-zinc-950/40 p-3 text-sm last:mb-0" wire:key="dispatch-{{ $dispatch->id }}">
                    <div class="font-semibold text-zinc-100">{{ $dispatch->dispatch_number }}</div>
                    <div class="text-xs text-zinc-400">{{ $dispatch->site?->name }} · {{ ucfirst(str_replace('_', ' ', $dispatch->status->value)) }}</div>
                    <div class="mt-1 text-xs text-zinc-300">{{ $dispatch->incident_location }}</div>
                    @if($dispatch->status->next())
                        <button type="button" wire:click="advanceDispatch({{ $dispatch->id }})" class="field-btn-warning mt-2 !py-2 text-xs">
                            Mark {{ strtolower($dispatch->status->next()->label()) }}
                        </button>
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    <section class="field-panel">
        <h2 class="field-panel-title">Shift confirm</h2>
        <p class="field-panel-meta mb-3">Confirm you received this assignment</p>
        <button type="button" wire:click="confirmMyShift" class="field-btn-primary" @disabled(! $activeAssignmentId)>Confirm shift</button>
    </section>

    <section class="field-panel">
        <h2 class="field-panel-title">Request shift swap</h2>
        <p class="field-panel-meta mb-3">Ask scheduling to release you. Optionally suggest a replacement.</p>
        <select wire:model="swapReplacementGuardId" class="field-input mb-2" @disabled(! $activeAssignmentId)>
            <option value="">No replacement suggested</option>
            @foreach($colleagueGuards as $colleague)
                <option value="{{ $colleague->id }}">{{ $colleague->full_name }}</option>
            @endforeach
        </select>
        <textarea wire:model="swapReason" rows="2" placeholder="Reason for swap (optional)" class="field-input mb-2" @disabled(! $activeAssignmentId)></textarea>
        <button type="button" wire:click="requestShiftSwap" class="field-btn-ghost" @disabled(! $activeAssignmentId)>Submit swap request</button>
        @if($mySwaps->isNotEmpty())
            <div class="mt-3 space-y-1 border-t border-zinc-800 pt-3 text-xs text-zinc-400">
                @foreach($mySwaps->take(5) as $swap)
                    <div>{{ $swap->shiftAssignment?->shift?->title }} — {{ ucfirst(\App\Support\EnumHelper::value($swap->status)) }}</div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="field-panel">
        <h2 class="field-panel-title">Open shifts</h2>
        <p class="field-panel-meta mb-3">Bid on shifts that need coverage</p>
        @forelse($openShifts as $shift)
            <div class="mb-2 rounded-md border border-zinc-800 bg-zinc-950/50 p-3 text-sm last:mb-0" wire:key="open-shift-{{ $shift->id }}">
                <div class="font-medium text-zinc-100">{{ $shift->title }}</div>
                <div class="text-xs tabular-nums text-zinc-400">{{ $shift->site?->name }} · {{ $shift->starts_at?->format('M j, H:i') }}</div>
                <div class="mt-1 text-xs font-medium tabular-nums text-amber-300">{{ $shift->activeAssignmentsCount() }}/{{ $shift->required_guards }} filled</div>
                <textarea wire:model="bidNotes.{{ $shift->id }}" rows="2" placeholder="Optional note for scheduling" class="field-input mt-2 !py-1.5 text-xs"></textarea>
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
    </section>

    @if($reportTemplates->isNotEmpty())
        <section class="field-panel">
            <h2 class="field-panel-title">Custom reports</h2>
            <p class="field-panel-meta mb-3">Fill and submit site report forms</p>
            <select wire:model="activeReportTemplateId" class="field-input mb-2">
                <option value="">Select report</option>
                @foreach($reportTemplates as $template)
                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                @endforeach
            </select>
            @if($activeReportTemplateId)
                @php $template = $reportTemplates->firstWhere('id', $activeReportTemplateId); @endphp
                @foreach($template?->fields ?? [] as $field)
                    <div class="mb-2">
                        <label class="mb-1 block text-xs text-zinc-400">{{ $field->label }}</label>
                        @if($field->field_type === 'textarea')
                            <textarea wire:model="reportData.{{ $field->id }}" class="field-input" rows="2"></textarea>
                        @else
                            <input wire:model="reportData.{{ $field->id }}" type="text" class="field-input">
                        @endif
                    </div>
                @endforeach
                <div class="flex gap-2">
                    <button type="button" wire:click="saveReportDraft" class="field-btn-ghost flex-1">Save draft</button>
                    <button type="button" wire:click="submitCustomReport" class="field-btn-success flex-1 !py-2.5">Submit</button>
                </div>
            @endif
        </section>
    @endif

    <section class="field-panel">
        <h2 class="field-panel-title">Passdown</h2>
        <p class="field-panel-meta mb-3">Handoff notes for the next guard</p>
        <textarea wire:model="passdownContent" rows="3" placeholder="Handoff notes for next guard..." class="field-input mb-2" @disabled(! $activeAssignmentId)></textarea>
        @error('passdownContent') <p class="mb-2 text-xs text-red-400">{{ $message }}</p> @enderror
        <button type="button" wire:click="savePassdown" class="field-btn-light" @disabled(! $activeAssignmentId)>Save passdown</button>
    </section>
    @endif

    <p class="pb-2 text-center text-[10px] text-zinc-600">Install this app from your browser menu for fullscreen field use.</p>
</div>
