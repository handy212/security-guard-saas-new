<div>
    <x-page-shell
        title="Payroll"
        description="Generate guard timesheets from attendance and export payroll CSV."
        :breadcrumbs="[
            ['label' => 'Back Office', 'href' => route('billing.hub')],
            ['label' => 'Payroll'],
        ]"
    >
        <x-slot:actions>
            @if ($canExport)
                <x-button variant="secondary" wire:click="exportQuickBooks">Export payroll CSV</x-button>
            @endif
            <x-button wire:click="openGenerate">Generate timesheet</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-back-office-nav /></x-slot:sidebar>

            <x-flash-status type="success" />

            <div class="stat-grid">
                <x-stat-card compact label="Timesheets" :value="$timesheets->count()" icon="plan" />
                <x-stat-card compact label="Active guards" :value="$guards->count()" icon="guards" tone="success" />
                <x-stat-card compact label="Payroll exports" :value="$payrollExports->count()" icon="billing" tone="info" />
                <x-stat-card compact label="Period" :value="$periodStart ? \Carbon\Carbon::parse($periodStart)->format('M Y') : '—'" icon="schedules" />
            </div>

            <div class="page-grid-2">
                <x-data-table title="Recent timesheets">
                    <x-table.head>
                        <tr>
                            <x-table.th>Guard</x-table.th>
                            <x-table.th>Period</x-table.th>
                            <x-table.th>Hours</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th align="right">Actions</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @forelse($timesheets as $sheet)
                            <tr class="table-row-hover" wire:key="timesheet-{{ $sheet->id }}">
                                <x-table.td><span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $sheet->assignedGuard?->full_name ?? '—' }}</span></x-table.td>
                                <x-table.td muted>{{ $sheet->period_start?->format('M j') }} – {{ $sheet->period_end?->format('M j, Y') }}</x-table.td>
                                <x-table.td muted>{{ $sheet->regular_hours }}h + {{ $sheet->overtime_hours }}h OT</x-table.td>
                                <x-table.td><x-badge :status="$sheet->status" /></x-table.td>
                                <x-table.td align="right">
                                    @if ($sheet->status === 'pending')
                                        <div class="table-inline-actions">
                                            <button type="button" wire:click="approveTimesheet({{ $sheet->id }})" class="table-action">Approve</button>
                                            <button type="button" wire:click="rejectTimesheet({{ $sheet->id }})" wire:confirm="Reject this timesheet?" class="table-action text-red-600">Reject</button>
                                        </div>
                                    @elseif ($sheet->status === 'approved')
                                        <button type="button" wire:click="rejectTimesheet({{ $sheet->id }})" wire:confirm="Reject this approved timesheet?" class="table-action text-red-600">Reject</button>
                                    @else
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endif
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="5">
                                <x-empty-state compact title="No timesheets" description="Generate a timesheet from attendance logs.">
                                    <x-slot:actions>
                                        <x-button size="sm" wire:click="openGenerate">Generate timesheet</x-button>
                                        <x-button size="sm" variant="secondary" :href="route('schedules.attendance')">Attendance</x-button>
                                    </x-slot:actions>
                                </x-empty-state>
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>

                <x-data-table title="Payroll exports">
                    <x-table.head>
                        <tr>
                            <x-table.th>Provider</x-table.th>
                            <x-table.th>Period</x-table.th>
                            <x-table.th align="right">Actions</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @forelse($payrollExports as $export)
                            <tr class="table-row-hover" wire:key="payroll-export-{{ $export->id }}">
                                <x-table.td>{{ ucfirst($export->provider) }}</x-table.td>
                                <x-table.td muted>{{ $export->period_start?->format('M j') }} – {{ $export->period_end?->format('M j, Y') }}</x-table.td>
                                <x-table.td align="right">
                                    @if ($canExport)
                                        <button type="button" wire:click="downloadPayrollExport({{ $export->id }})" class="text-xs font-medium text-accent-600 hover:underline">Download</button>
                                    @endif
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="3">
                                <x-empty-state compact title="No payroll exports" description="Export CSV from completed shifts for accounting.">
                                    @if ($canExport)
                                        <x-slot:actions>
                                            <x-button size="sm" variant="secondary" wire:click="exportQuickBooks">Export payroll CSV</x-button>
                                        </x-slot:actions>
                                    @endif
                                </x-empty-state>
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($showGenerateForm)
        <x-drawer title="Generate timesheet" description="Build a payroll timesheet from attendance for a guard." width="md" close-method="closeGenerate">
            <x-drawer-form wire:submit="generateTimesheet" submit-label="Generate timesheet" close-method="closeGenerate" target="generateTimesheet">
                <x-form-section title="Period">
                    <x-select wire:model="guardId" label="Guard *" class="sm:col-span-2">
                        <option value="">Select guard</option>
                        @foreach($guards as $guard)
                            <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="periodStart" label="Period start *" type="date" />
                    <x-input wire:model="periodEnd" label="Period end *" type="date" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
