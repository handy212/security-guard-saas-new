<div class="space-y-4" wire:poll.60s="$refresh">
    @if($statusMessage)
        <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm">{{ $statusMessage }}</div>
    @endif

    @error('action')
        <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm">{{ $message }}</div>
    @enderror

    @unless($hasGuardProfile)
        <div class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-sm">
            Your account is not linked to a guard profile. Contact your supervisor to use clock-in, patrol, and shift features.
        </div>
    @endunless

    @if($hasGuardProfile && $isOnDuty)
        <div class="rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-sm font-medium text-emerald-200">
            You are clocked in. Remember to clock out when your shift ends.
        </div>
    @endif

    <section id="assignments" class="scroll-mt-20 rounded-lg border border-zinc-700 bg-zinc-800 p-4">
        <h2 class="font-bold">Today's assignments</h2>
        @forelse($assignments as $assignment)
            <label class="mt-2 flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-700 p-3 {{ $activeAssignmentId === $assignment->id ? 'border-accent-500 bg-accent-500/5' : '' }}">
                <input type="radio" wire:model.live="activeAssignmentId" value="{{ $assignment->id }}" class="accent-500">
                <div>
                    <div class="font-medium">{{ $assignment->shift?->title ?? $assignment->shift?->site?->name }}</div>
                    <div class="text-xs text-zinc-400">{{ $assignment->shift?->site?->name }} · {{ $assignment->shift?->starts_at?->format('M j, H:i') }} – {{ $assignment->shift?->ends_at?->format('H:i') }}</div>
                    <div class="mt-0.5 text-[10px] uppercase tracking-wide text-zinc-500">{{ ucfirst(\App\Support\EnumHelper::value($assignment->status)) }}</div>
                </div>
            </label>
        @empty
            <p class="mt-2 text-sm text-zinc-400">No shifts scheduled for today.</p>
        @endforelse
    </section>

    @if($hasGuardProfile)
    <section class="grid grid-cols-2 gap-3">
        <button type="button"
            onclick="window.guardWithGeo(@this, 'clockIn', 'clock_in', (c, w) => ({ shift_assignment_id: w.activeAssignmentId, latitude: c.lat, longitude: c.lng }))"
            wire:loading.attr="disabled"
            wire:target="clockIn"
            class="rounded-lg bg-emerald-600 py-4 font-bold disabled:cursor-not-allowed disabled:opacity-40"
            @disabled(! $activeAssignmentId || $isOnDuty)>
            <span wire:loading.remove wire:target="clockIn">Clock In</span>
            <span wire:loading wire:target="clockIn">Working…</span>
        </button>
        <button type="button"
            onclick="window.guardWithGeo(@this, 'clockOut', 'clock_out', (c, w) => ({ attendance_log_id: w.activeAttendanceId, latitude: c.lat, longitude: c.lng }))"
            wire:loading.attr="disabled"
            wire:target="clockOut"
            class="rounded-lg bg-amber-600 py-4 font-bold disabled:cursor-not-allowed disabled:opacity-40"
            @disabled(! $isOnDuty)>
            <span wire:loading.remove wire:target="clockOut">Clock Out</span>
            <span wire:loading wire:target="clockOut">Working…</span>
        </button>
        <button type="button"
            onclick="window.guardWithGeo(@this, 'updateLocation', 'location', (c) => ({ latitude: c.lat, longitude: c.lng }))"
            wire:loading.attr="disabled"
            wire:target="updateLocation"
            class="col-span-2 rounded-lg bg-accent-600 py-3 text-base font-bold disabled:opacity-60">
            <span wire:loading.remove wire:target="updateLocation">Update GPS location</span>
            <span wire:loading wire:target="updateLocation">Updating…</span>
        </button>
    </section>
    @if(! $activeAssignmentId)
        <p class="text-center text-xs text-zinc-500">Select a shift above to clock in or submit passdown notes.</p>
    @endif

    <section
        id="sos"
        class="scroll-mt-20 rounded-xl border-2 border-red-500/60 bg-red-950/40 p-4"
        x-data="{ armed: false }"
    >
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-red-100">Emergency SOS</h2>
                <p class="mt-1 text-xs text-red-200/80">Alerts dispatch immediately with your GPS position.</p>
            </div>
            <span class="rounded-full bg-red-500/20 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-red-200">Critical</span>
        </div>

        <template x-if="! armed">
            <button
                type="button"
                @click="armed = true"
                class="mt-4 w-full rounded-lg bg-red-600 py-4 text-lg font-bold text-white shadow-lg shadow-red-900/40"
            >
                Raise SOS alert
            </button>
        </template>

        <div x-show="armed" x-cloak class="mt-4 space-y-3">
            <p class="text-sm font-medium text-red-100">Confirm emergency — dispatch will be notified.</p>
            <div class="grid grid-cols-2 gap-3">
                <button type="button" @click="armed = false" class="rounded-lg border border-red-400/40 py-3 font-semibold text-red-100">Cancel</button>
                <button type="button"
                    @click="armed = false; window.guardWithGeo(@this, 'raiseSos', 'sos', (c) => ({ latitude: c.lat, longitude: c.lng, message: 'SOS (offline queued)' }))"
                    wire:loading.attr="disabled"
                    wire:target="raiseSos"
                    class="rounded-lg bg-red-600 py-3 font-bold text-white disabled:opacity-60">
                    <span wire:loading.remove wire:target="raiseSos">Confirm SOS</span>
                    <span wire:loading wire:target="raiseSos">Sending…</span>
                </button>
            </div>
        </div>
    </section>

    <section id="patrol" class="scroll-mt-20 rounded-lg border border-zinc-700 bg-zinc-800 p-4">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-bold">Patrol</h2>
            @if($isOnDuty)
                <span class="text-xs text-emerald-400">On shift</span>
            @endif
        </div>

        @if($activePatrols->isNotEmpty())
            <div class="mb-3 space-y-2">
                <div class="text-xs uppercase text-zinc-400">Active sessions</div>
                @foreach($activePatrols as $patrol)
                    <button type="button" wire:click="$set('patrolSessionId', {{ $patrol->id }})"
                        class="w-full rounded-lg border px-3 py-2 text-left text-sm {{ $patrolSessionId === $patrol->id ? 'border-accent-500 bg-accent-500/10' : 'border-zinc-600' }}">
                        #{{ $patrol->id }} — {{ $patrol->route?->name }}
                    </button>
                @endforeach
            </div>
        @endif

        @if($patrolRoutes->isNotEmpty())
            <div class="mb-3">
                <div class="mb-1 text-xs uppercase text-zinc-400">Start new patrol</div>
                <div class="flex flex-wrap gap-2">
                    @foreach($patrolRoutes as $route)
                        <button type="button" wire:click="startPatrol({{ $route->id }})"
                            class="rounded-lg border border-zinc-600 px-3 py-1 text-xs hover:border-accent-500">
                            {{ $route->name }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    <section id="scan" class="scroll-mt-20 rounded-lg border border-zinc-700 bg-zinc-800 p-4">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-bold">Checkpoint scan</h2>
            <div class="flex gap-2">
                <button type="button" wire:click="toggleNfcScanner" class="rounded-lg border border-slate-600 px-3 py-1 text-xs font-semibold">
                    {{ $showNfcScanner ? 'Stop NFC' : 'Scan NFC' }}
                </button>
                <button type="button" wire:click="toggleScanner" class="rounded-lg bg-accent-600 px-3 py-1 text-xs font-semibold">
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
            <p class="mb-3 text-xs text-slate-400">Hold device near NFC tag…</p>
        @endif

        @if($showScanner)
            <div id="qr-reader" class="mb-3 overflow-hidden rounded-lg border border-zinc-600" wire:ignore></div>
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

        <select wire:model="patrolSessionId" class="mb-2 w-full rounded-lg border-zinc-600 bg-zinc-900 px-3 py-2 text-sm">
            <option value="">Select patrol session</option>
            @foreach($activePatrols as $patrol)
                <option value="{{ $patrol->id }}">#{{ $patrol->id }} — {{ $patrol->route?->name }}</option>
            @endforeach
        </select>
        <input wire:model="checkpointCode" type="text" placeholder="QR / NFC checkpoint code" class="mb-2 w-full rounded-lg border-zinc-600 bg-zinc-900 px-3 py-2 text-sm">
        <button type="button"
            onclick="window.guardWithGeo(@this, 'scanCheckpoint', 'checkpoint_scan', (c, w) => ({ patrol_session_id: w.patrolSessionId, checkpoint_code: w.checkpointCode, latitude: c.lat, longitude: c.lng }))"
            class="w-full rounded-lg bg-zinc-100 py-2 font-semibold text-zinc-900">Submit scan</button>
    </section>

    @if($dispatches->isNotEmpty())
        <section class="rounded-lg border border-amber-500/40 bg-amber-500/5 p-4">
            <h2 class="mb-2 font-bold text-amber-200">Active dispatches</h2>
            @foreach($dispatches as $dispatch)
                <div class="mb-2 rounded-lg border border-amber-500/30 p-3 text-sm" wire:key="dispatch-{{ $dispatch->id }}">
                    <div class="font-semibold">{{ $dispatch->dispatch_number }}</div>
                    <div class="text-xs text-zinc-400">{{ $dispatch->site?->name }} · {{ ucfirst(str_replace('_', ' ', $dispatch->status->value)) }}</div>
                    <div class="mt-1 text-xs">{{ $dispatch->incident_location }}</div>
                    @if($dispatch->status->next())
                        <button type="button" wire:click="advanceDispatch({{ $dispatch->id }})" class="mt-2 w-full rounded-lg bg-amber-600 py-2 text-xs font-semibold">
                            Mark {{ strtolower($dispatch->status->next()->label()) }}
                        </button>
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    <section class="rounded-lg border border-zinc-700 bg-zinc-800 p-4">
        <h2 class="mb-2 font-bold">Shift confirm</h2>
        <button type="button" wire:click="confirmMyShift" class="w-full rounded-lg bg-accent-600 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-40" @disabled(! $activeAssignmentId)>Confirm shift</button>
    </section>

    <section class="rounded-lg border border-zinc-700 bg-zinc-800 p-4">
        <h2 class="mb-2 font-bold">Request shift swap</h2>
        <p class="mb-2 text-xs text-zinc-400">Ask scheduling to release you from a shift. Optionally suggest a replacement guard.</p>
        <select wire:model="swapReplacementGuardId" class="mb-2 w-full rounded-lg border-zinc-600 bg-zinc-900 px-3 py-2 text-sm" @disabled(! $activeAssignmentId)>
            <option value="">No replacement suggested</option>
            @foreach($colleagueGuards as $colleague)
                <option value="{{ $colleague->id }}">{{ $colleague->full_name }}</option>
            @endforeach
        </select>
        <textarea wire:model="swapReason" rows="2" placeholder="Reason for swap (optional)" class="mb-2 w-full rounded-lg border-zinc-600 bg-zinc-900 px-3 py-2 text-sm" @disabled(! $activeAssignmentId)></textarea>
        <button type="button" wire:click="requestShiftSwap" class="w-full rounded-lg border border-zinc-600 bg-zinc-800 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-40" @disabled(! $activeAssignmentId)>Submit swap request</button>
        @if($mySwaps->isNotEmpty())
            <div class="mt-3 space-y-1 border-t border-zinc-700 pt-3 text-xs text-zinc-400">
                @foreach($mySwaps->take(5) as $swap)
                    <div>{{ $swap->shiftAssignment?->shift?->title }} — {{ ucfirst(\App\Support\EnumHelper::value($swap->status)) }}</div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="rounded-lg border border-zinc-700 bg-zinc-800 p-4">
        <h2 class="mb-2 font-bold">Open shifts</h2>
        <p class="mb-2 text-xs text-zinc-400">Bid on shifts that need coverage. Scheduling will review your bid.</p>
        @forelse($openShifts as $shift)
            <div class="mb-2 rounded-lg border border-zinc-600 p-3 text-sm" wire:key="open-shift-{{ $shift->id }}">
                <div class="font-medium">{{ $shift->title }}</div>
                <div class="text-xs text-zinc-400">{{ $shift->site?->name }} · {{ $shift->starts_at?->format('M j, H:i') }}</div>
                <div class="mt-1 text-xs text-amber-300">{{ $shift->activeAssignmentsCount() }}/{{ $shift->required_guards }} filled</div>
                <textarea wire:model="bidNotes.{{ $shift->id }}" rows="2" placeholder="Optional note for scheduling" class="mt-2 w-full rounded border border-zinc-600 bg-zinc-900 px-2 py-1 text-xs"></textarea>
                <button type="button" wire:click="bidOnShift({{ $shift->id }})" class="mt-2 w-full rounded-lg bg-emerald-700 py-2 text-xs font-semibold">Place bid</button>
            </div>
        @empty
            <p class="text-sm text-zinc-400">No open shifts right now.</p>
        @endforelse
        @if($myBids->isNotEmpty())
            <div class="mt-3 border-t border-zinc-700 pt-3">
                <div class="mb-1 text-xs font-semibold uppercase text-zinc-500">Your bids</div>
                @foreach($myBids->take(5) as $bid)
                    <div class="text-xs text-zinc-400">{{ $bid->shift?->title }} — {{ ucfirst(\App\Support\EnumHelper::value($bid->status)) }}</div>
                @endforeach
            </div>
        @endif
    </section>

    @if($reportTemplates->isNotEmpty())
        <section class="rounded-lg border border-zinc-700 bg-zinc-800 p-4">
            <h2 class="mb-2 font-bold">Custom reports</h2>
            <select wire:model="activeReportTemplateId" class="mb-2 w-full rounded-lg border-zinc-600 bg-zinc-900 px-3 py-2 text-sm">
                <option value="">Select report</option>
                @foreach($reportTemplates as $template)
                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                @endforeach
            </select>
            @if($activeReportTemplateId)
                @php $template = $reportTemplates->firstWhere('id', $activeReportTemplateId); @endphp
                @foreach($template?->fields ?? [] as $field)
                    <div class="mb-2">
                        <label class="text-xs text-zinc-400">{{ $field->label }}</label>
                        @if($field->field_type === 'textarea')
                            <textarea wire:model="reportData.{{ $field->id }}" class="w-full rounded-lg border-zinc-600 bg-zinc-900 px-3 py-2 text-sm" rows="2"></textarea>
                        @else
                            <input wire:model="reportData.{{ $field->id }}" type="text" class="w-full rounded-lg border-zinc-600 bg-zinc-900 px-3 py-2 text-sm">
                        @endif
                    </div>
                @endforeach
                <div class="flex gap-2">
                    <button type="button" wire:click="saveReportDraft" class="flex-1 rounded-lg border border-zinc-600 py-2 text-sm">Save draft</button>
                    <button type="button" wire:click="submitCustomReport" class="flex-1 rounded-lg bg-emerald-600 py-2 text-sm font-semibold">Submit</button>
                </div>
            @endif
        </section>
    @endif

    <section class="rounded-lg border border-zinc-700 bg-zinc-800 p-4">
        <h2 class="mb-2 font-bold">Passdown</h2>
        <textarea wire:model="passdownContent" rows="3" placeholder="Handoff notes for next guard..." class="mb-2 w-full rounded-lg border-zinc-600 bg-zinc-900 px-3 py-2 text-sm" @disabled(! $activeAssignmentId)></textarea>
        @error('passdownContent') <p class="mb-2 text-xs text-red-400">{{ $message }}</p> @enderror
        <button type="button" wire:click="savePassdown" class="w-full rounded-lg bg-zinc-100 py-2 font-semibold text-zinc-900 disabled:cursor-not-allowed disabled:opacity-40" @disabled(! $activeAssignmentId)>Save passdown</button>
    </section>
    @endif

    <p class="text-center text-[10px] text-zinc-500">Install this app from your browser menu for fullscreen field use.</p>
</div>
