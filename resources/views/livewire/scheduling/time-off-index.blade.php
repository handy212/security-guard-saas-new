<div>
    <x-page-shell title="Time Off" description="Manage leave requests and weekly guard availability.">
        <x-schedules-nav />
        <x-flash-status />

        <div class="stat-grid">
            <x-stat-card compact label="Pending review" :value="$pendingCount" icon="pause" :tone="$pendingCount ? 'warning' : 'success'" :href="route('schedules.time-off')" />
            <x-stat-card compact label="On this page" :value="$leaveRequests->total()" icon="users" />
            <x-stat-card compact label="Availability rows" :value="$availabilities->count()" icon="schedules" tone="info" />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-form-card :title="$editingLeaveId ? 'Edit time off request' : 'Submit time off request'">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-select wire:model="leaveForm.guard_id" label="Guard *">
                        <option value="">Select</option>
                        @foreach($guards as $g)<option value="{{ $g->id }}">{{ $g->full_name }}</option>@endforeach
                    </x-select>
                    <x-select wire:model="leaveForm.type" label="Type *">
                        @foreach($leaveTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="leaveForm.starts_on" type="date" label="Starts *" />
                    <x-input wire:model="leaveForm.ends_on" type="date" label="Ends *" />
                    <x-textarea wire:model="leaveForm.reason" label="Reason" class="sm:col-span-2" rows="2" />
                </div>
                <div class="mt-3 flex gap-2">
                    <x-button wire:click="submitLeave">{{ $editingLeaveId ? 'Save changes' : 'Submit request' }}</x-button>
                    @if($editingLeaveId)
                        <x-button variant="secondary" wire:click="cancelLeaveEdit">Cancel edit</x-button>
                    @endif
                </div>
            </x-form-card>

            <x-form-card :title="$editingAvailabilityId ? 'Edit availability' : 'Add weekly availability'">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-select wire:model="availabilityForm.guard_id" label="Guard *">
                        <option value="">Select</option>
                        @foreach($guards as $g)<option value="{{ $g->id }}">{{ $g->full_name }}</option>@endforeach
                    </x-select>
                    <x-select wire:model="availabilityForm.weekday" label="Day *">
                        @foreach($weekdays as $d => $label)
                            <option value="{{ $d }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="availabilityForm.starts_at" type="time" label="From *" />
                    <x-input wire:model="availabilityForm.ends_at" type="time" label="To *" />
                    <label class="flex items-center gap-2 text-sm sm:col-span-2">
                        <input type="checkbox" wire:model="availabilityForm.is_available" class="rounded border-zinc-300">
                        Available during this window
                    </label>
                </div>
                <div class="mt-3 flex gap-2">
                    <x-button wire:click="saveAvailability">{{ $editingAvailabilityId ? 'Update availability' : 'Save availability' }}</x-button>
                    @if($editingAvailabilityId)
                        <x-button variant="secondary" wire:click="cancelAvailabilityEdit">Cancel edit</x-button>
                    @endif
                </div>
            </x-form-card>
        </div>

        <x-section-card title="Leave requests" class="mt-4" :description="$pendingCount ? $pendingCount.' pending approval' : null">
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
                        <tr wire:key="leave-{{ $req->id }}">
                            <x-table.td>{{ $req->assignedGuard?->full_name ?? '—' }}</x-table.td>
                            <x-table.td>{{ $leaveTypes[$req->type] ?? ucfirst($req->type ?? 'leave') }}</x-table.td>
                            <x-table.td muted>{{ $req->starts_on?->format('M j') }} – {{ $req->ends_on?->format('M j, Y') }}</x-table.td>
                            <x-table.td muted>{{ Str::limit($req->reason, 40) ?: '—' }}</x-table.td>
                            <x-table.td>
                                @if($conflicts > 0)
                                    @if($conflict['scheduleUrl'])
                                        <a href="{{ $conflict['scheduleUrl'] }}" class="text-xs font-medium text-amber-700 underline hover:text-amber-900">{{ $conflicts }} shift{{ $conflicts === 1 ? '' : 's' }}</a>
                                    @else
                                        <span class="text-xs font-medium text-amber-700">{{ $conflicts }} shift{{ $conflicts === 1 ? '' : 's' }}</span>
                                    @endif
                                @else
                                    <span class="text-xs text-zinc-400">None</span>
                                @endif
                            </x-table.td>
                            <x-table.td><x-badge :status="$req->status" /></x-table.td>
                            <x-table.td align="right">
                                @if(\App\Support\EnumHelper::is($req->status, 'pending'))
                                    <div class="flex justify-end gap-1">
                                        <x-button size="sm" variant="secondary" wire:click="editLeave({{ $req->id }})">Edit</x-button>
                                        <x-button size="sm" wire:click="approveLeave({{ $req->id }})" :disabled="$conflicts > 0" title="{{ $conflicts > 0 ? 'Resolve shift conflicts first' : '' }}">Approve</x-button>
                                        <x-button size="sm" variant="secondary" wire:click="rejectLeave({{ $req->id }})">Reject</x-button>
                                        <button type="button" wire:click="cancelLeave({{ $req->id }})" wire:confirm="Cancel this request?" class="text-xs text-red-600 hover:underline">Cancel</button>
                                    </div>
                                @elseif(\App\Support\EnumHelper::is($req->status, 'approved') && $req->approver)
                                    <span class="text-xs text-zinc-400">By {{ $req->approver->name }}</span>
                                @endif
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="7"><x-empty-state compact title="No time off requests" /></x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>
            <x-pagination :paginator="$leaveRequests" />
        </x-section-card>

        <x-section-card title="Guard availability" class="mt-4">
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
                        <tr wire:key="avail-{{ $a->id }}">
                            <x-table.td>{{ $a->assignedGuard?->full_name }}</x-table.td>
                            <x-table.td>{{ $weekdays[$a->weekday] ?? $a->weekday }}</x-table.td>
                            <x-table.td muted>{{ substr((string) $a->starts_at, 0, 5) }} – {{ substr((string) $a->ends_at, 0, 5) }}</x-table.td>
                            <x-table.td>
                                @if($a->is_available)
                                    <span class="text-xs text-emerald-700">Yes</span>
                                @else
                                    <span class="text-xs text-zinc-500">Unavailable</span>
                                @endif
                            </x-table.td>
                            <x-table.td align="right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="editAvailability({{ $a->id }})" class="text-xs text-zinc-600 hover:underline">Edit</button>
                                    <button type="button" wire:click="deleteAvailability({{ $a->id }})" wire:confirm="Remove this availability?" class="text-xs text-red-600 hover:underline">Remove</button>
                                </div>
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="5"><x-empty-state compact title="No availability on file" description="Add weekly availability windows above." /></x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>
        </x-section-card>
    </x-page-shell>
</div>
