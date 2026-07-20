<div>
    <x-page-shell title="Time Off" description="Manage leave requests and weekly guard availability.">
        <x-slot:actions>
            <x-button variant="secondary" wire:click="openAvailabilityForm">Add availability</x-button>
            <x-button wire:click="openLeaveForm">Request time off</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>

            <x-flash-status />

            <div class="stat-grid">
                <x-stat-card compact label="Pending review" :value="$pendingCount" icon="pause" :tone="$pendingCount ? 'warning' : 'success'" />
                <x-stat-card compact label="On this page" :value="$leaveRequests->total()" icon="users" />
                <x-stat-card compact label="Availability rows" :value="$availabilities->count()" icon="schedules" tone="info" />
            </div>

            <x-section-card title="Leave requests" :description="$pendingCount ? $pendingCount.' pending approval' : 'No pending requests'">
                <x-page-toolbar class="mb-3 !border-0 !p-0 !shadow-none">
                    <x-slot:tabs>
                        <x-segment-control field="leaveFilter" :active="$leaveFilter" :options="collect($leaveStatuses)->prepend('All', 'all')->all()" />
                    </x-slot:tabs>
                    <x-slot:controls>
                        <x-filter-select wire:model.live="guardFilter">
                            <option value="">All guards</option>
                            @foreach($guards as $g)
                                <option value="{{ $g->id }}">{{ $g->full_name }}</option>
                            @endforeach
                        </x-filter-select>
                    </x-slot:controls>
                </x-page-toolbar>

                <x-data-table>
                    <x-table.head>
                        <tr>
                            <x-table.th>Guard</x-table.th>
                            <x-table.th>Type</x-table.th>
                            <x-table.th>Dates</x-table.th>
                            <x-table.th>Reason</x-table.th>
                            <x-table.th>Conflicts</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th align="right">Actions</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @forelse($leaveRequests as $req)
                            @php
                                $conflict = $conflictMeta[$req->id] ?? ['count' => 0, 'scheduleUrl' => null];
                                $conflicts = $conflict['count'];
                            @endphp
                            <tr class="table-row-hover" wire:key="leave-{{ $req->id }}">
                                <x-table.td class="font-medium text-zinc-900 dark:text-zinc-100">{{ $req->assignedGuard?->full_name ?? '—' }}</x-table.td>
                                <x-table.td muted>{{ $leaveTypes[$req->type] ?? ucfirst($req->type ?? 'leave') }}</x-table.td>
                                <x-table.td muted class="tabular-nums">{{ $req->starts_on?->format('M j') }} – {{ $req->ends_on?->format('M j, Y') }}</x-table.td>
                                <x-table.td muted>{{ Str::limit($req->reason, 40) ?: '—' }}</x-table.td>
                                <x-table.td>
                                    @if($conflicts > 0)
                                        @if($conflict['scheduleUrl'])
                                            <a href="{{ $conflict['scheduleUrl'] }}" class="text-xs font-medium text-amber-700 underline hover:text-amber-900 dark:text-amber-400">{{ $conflicts }} shift{{ $conflicts === 1 ? '' : 's' }}</a>
                                        @else
                                            <span class="text-xs font-medium text-amber-700 dark:text-amber-400">{{ $conflicts }} shift{{ $conflicts === 1 ? '' : 's' }}</span>
                                        @endif
                                    @else
                                        <span class="text-xs text-zinc-400">None</span>
                                    @endif
                                </x-table.td>
                                <x-table.td><x-badge :status="$req->status" /></x-table.td>
                                <x-table.td align="right">
                                    @if(\App\Support\EnumHelper::is($req->status, 'pending'))
                                        <div class="table-inline-actions justify-end">
                                            <x-button size="sm" variant="secondary" wire:click="editLeave({{ $req->id }})">Edit</x-button>
                                            <x-button size="sm" wire:click="approveLeave({{ $req->id }})" :disabled="$conflicts > 0" title="{{ $conflicts > 0 ? 'Resolve shift conflicts first' : '' }}">Approve</x-button>
                                            <x-button size="sm" variant="secondary" wire:click="rejectLeave({{ $req->id }})">Reject</x-button>
                                            <button type="button" wire:click="cancelLeave({{ $req->id }})" wire:confirm="Cancel this request?" class="table-action-danger">Cancel</button>
                                        </div>
                                    @elseif(\App\Support\EnumHelper::is($req->status, 'approved') && $req->approver)
                                        <span class="text-xs text-zinc-400">By {{ $req->approver->name }}</span>
                                    @endif
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="7">
                                <x-empty-state compact title="No time off requests" description="Submit a leave request to block scheduling conflicts.">
                                    <x-slot:actions>
                                        <x-button size="sm" wire:click="openLeaveForm">Request time off</x-button>
                                    </x-slot:actions>
                                </x-empty-state>
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
                <x-pagination :paginator="$leaveRequests" />
            </x-section-card>

            <x-section-card title="Guard availability" class="mt-4" description="Weekly windows used when staffing open shifts.">
                <div class="mb-3 max-w-xs">
                    <x-filter-select wire:model.live="availabilityGuardFilter" label="Filter by guard">
                        <option value="">All guards</option>
                        @foreach($guards as $g)
                            <option value="{{ $g->id }}">{{ $g->full_name }}</option>
                        @endforeach
                    </x-filter-select>
                </div>
                <x-data-table>
                    <x-table.head>
                        <tr>
                            <x-table.th>Guard</x-table.th>
                            <x-table.th>Day</x-table.th>
                            <x-table.th>Window</x-table.th>
                            <x-table.th>Available</x-table.th>
                            <x-table.th align="right">Actions</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @forelse($availabilities as $a)
                            <tr class="table-row-hover" wire:key="avail-{{ $a->id }}">
                                <x-table.td class="font-medium text-zinc-900 dark:text-zinc-100">{{ $a->assignedGuard?->full_name }}</x-table.td>
                                <x-table.td muted>{{ $weekdays[$a->weekday] ?? $a->weekday }}</x-table.td>
                                <x-table.td muted class="tabular-nums">{{ substr((string) $a->starts_at, 0, 5) }} – {{ substr((string) $a->ends_at, 0, 5) }}</x-table.td>
                                <x-table.td>
                                    @if($a->is_available)
                                        <span class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Yes</span>
                                    @else
                                        <span class="text-xs text-zinc-500">Unavailable</span>
                                    @endif
                                </x-table.td>
                                <x-table.td align="right">
                                    <div class="table-inline-actions justify-end">
                                        <button type="button" wire:click="editAvailability({{ $a->id }})" class="table-action">Edit</button>
                                        <button type="button" wire:click="deleteAvailability({{ $a->id }})" wire:confirm="Remove this availability?" class="table-action-danger">Remove</button>
                                    </div>
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="5">
                                <x-empty-state compact title="No availability on file" description="Add weekly availability windows for staffing.">
                                    <x-slot:actions>
                                        <x-button size="sm" wire:click="openAvailabilityForm">Add availability</x-button>
                                    </x-slot:actions>
                                </x-empty-state>
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
            </x-section-card>
        </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($showLeaveForm)
        <x-drawer
            :title="$editingLeaveId ? 'Edit time off request' : 'Request time off'"
            :description="$editingLeaveId ? 'Update dates or reason while the request is still pending.' : 'Submit leave so schedulers can avoid conflicts.'"
            width="md"
            close-method="closeLeaveForm"
        >
            <x-drawer-form wire:submit.prevent="submitLeave" :submit-label="$editingLeaveId ? 'Save changes' : 'Submit request'" close-method="closeLeaveForm" target="submitLeave">
                <x-form-section title="Request">
                    <x-select wire:model="leaveForm.guard_id" label="Guard *" class="sm:col-span-2">
                        <option value="">Select</option>
                        @foreach($guards as $g)
                            <option value="{{ $g->id }}">{{ $g->full_name }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model="leaveForm.type" label="Type *" class="sm:col-span-2">
                        @foreach($leaveTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="leaveForm.starts_on" type="date" label="Starts *" />
                    <x-input wire:model="leaveForm.ends_on" type="date" label="Ends *" />
                    <x-textarea wire:model="leaveForm.reason" label="Reason" class="sm:col-span-2" rows="2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif

    @if ($showAvailabilityForm)
        <x-drawer
            :title="$editingAvailabilityId ? 'Edit availability' : 'Add weekly availability'"
            :description="$editingAvailabilityId ? 'Update this recurring weekly window.' : 'Set when a guard is available for open-shift staffing.'"
            width="md"
            close-method="closeAvailabilityForm"
        >
            <x-drawer-form wire:submit.prevent="saveAvailability" :submit-label="$editingAvailabilityId ? 'Update availability' : 'Save availability'" close-method="closeAvailabilityForm" target="saveAvailability">
                <x-form-section title="Weekly window">
                    <x-select wire:model="availabilityForm.guard_id" label="Guard *" class="sm:col-span-2">
                        <option value="">Select</option>
                        @foreach($guards as $g)
                            <option value="{{ $g->id }}">{{ $g->full_name }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model="availabilityForm.weekday" label="Day *" class="sm:col-span-2">
                        @foreach($weekdays as $d => $label)
                            <option value="{{ $d }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="availabilityForm.starts_at" type="time" label="From *" />
                    <x-input wire:model="availabilityForm.ends_at" type="time" label="To *" />
                    <label class="sm:col-span-2 flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="checkbox" wire:model="availabilityForm.is_available" class="rounded border-zinc-300 dark:border-zinc-600">
                        Available during this window
                    </label>
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
