<div>
    <x-page-shell title="Payroll" compact stable-header>
        <x-slot:actions>
            <x-button variant="secondary" wire:click="openGenerate">Single-guard adjustment</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout class="billing-workspace">
            <x-slot:sidebar><x-back-office-nav /></x-slot:sidebar>

            <x-flash-status type="success" />

            <x-section-card title="Payroll period" compact>
                <form wire:submit="preparePayroll" class="flex flex-wrap items-start gap-2">
                    <x-input wire:model.live="periodStart" label="Start date" type="date" />
                    <x-input wire:model.live="periodEnd" label="End date" type="date" />
                    <x-button type="submit" size="sm" class="mt-6" wire:target="preparePayroll" loading-text="Preparing…">Prepare payroll</x-button>
                    @if ($canExport)
                        <x-button type="button" size="sm" variant="secondary" class="mt-6" wire:click="exportQuickBooks" wire:target="exportQuickBooks"
                            :disabled="$pendingTimesheets > 0 || $approvedTimesheets === 0">Export approved CSV</x-button>
                    @endif
                </form>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Prepares drafts for guards with recorded work and a monthly rate. Resolve pending timesheets before exporting.</p>
                @if ($preparation)
                    <p role="status" class="mt-2 text-sm">
                        {{ $preparation['created'] }} drafted · {{ $preparation['existing'] }} already exist
                        · {{ $preparation['missing_rate'] }} missing a rate · {{ $preparation['overlapping'] }} have an overlapping period
                    </p>
                @endif
            </x-section-card>

            <div class="stat-grid">
                <x-stat-card compact label="Period timesheets" :value="$totalTimesheets" icon="plan" />
                <x-stat-card compact label="Pending approval" :value="$pendingTimesheets" icon="pause" :tone="$pendingTimesheets ? 'warning' : 'success'" />
                <x-stat-card compact label="Approved" :value="$approvedTimesheets" icon="check" tone="success" />
                <x-stat-card compact label="Payroll exports" :value="$totalPayrollExports" icon="billing" tone="info" />
            </div>

            <div class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <x-search-input wire:model.live.debounce.300ms="guardSearch" placeholder="Search guard or employee number" aria-label="Search period timesheets" />
                    @if ($pendingTimesheets)
                        <x-button size="sm" variant="secondary" wire:click="approvePeriod" wire:confirm="Approve all {{ $pendingTimesheets }} pending timesheets for {{ $periodStart }} through {{ $periodEnd }}?">Approve all pending</x-button>
                    @endif
                </div>
                <x-data-table title="Timesheets for this period">
                    <x-table.head>
                        <tr>
                            <x-table.th>Guard</x-table.th>
                            <x-table.th>Period</x-table.th>
                            <x-table.th>Hours</x-table.th>
                            <x-table.th>Gross pay</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th align="right">Actions</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @forelse($timesheets as $sheet)
                            <tr class="table-row-hover" wire:key="timesheet-{{ $sheet->id }}">
                                <x-table.td><span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $sheet->assignedGuard?->full_name ?? '—' }}</span></x-table.td>
                                <x-table.td muted class="tabular-nums">{{ $sheet->period_start?->format('M j') }} – {{ $sheet->period_end?->format('M j, Y') }}</x-table.td>
                                <x-table.td muted class="tabular-nums">{{ $sheet->regular_hours }}h + {{ $sheet->overtime_hours }}h OT</x-table.td>
                                <x-table.td class="tabular-nums">{{ money($sheet->gross_pay) }}</x-table.td>
                                <x-table.td><x-badge :status="$sheet->status" /></x-table.td>
                                <x-table.td align="right">
                                    @if (in_array($sheet->status, ['draft', 'pending'], true))
                                        <div class="table-inline-actions">
                                            <button type="button" wire:click="approveTimesheet({{ $sheet->id }})" class="table-action">Approve</button>
                                            <button type="button" wire:click="rejectTimesheet({{ $sheet->id }})" wire:confirm="Reject this timesheet?" class="table-action-danger">Reject</button>
                                        </div>
                                    @elseif ($sheet->status === 'approved')
                                        <button type="button" wire:click="rejectTimesheet({{ $sheet->id }})" wire:confirm="Reject this approved timesheet?" class="table-action-danger">Reject</button>
                                    @else
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endif
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="6">
                                <x-empty-state inline :title="$totalTimesheets ? 'No matching guards' : 'No timesheets for this period'" :description="$totalTimesheets ? null : 'Prepare payroll using the selected dates.'">
                                    <x-slot:actions>
                                        <x-button size="sm" variant="secondary" :href="route('schedules.attendance')">Attendance</x-button>
                                    </x-slot:actions>
                                </x-empty-state>
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
                <x-pagination :paginator="$timesheets" />

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
                                <x-table.td class="font-medium">{{ ucfirst($export->provider) }}</x-table.td>
                                <x-table.td muted class="tabular-nums">{{ $export->period_start?->format('M j') }} – {{ $export->period_end?->format('M j, Y') }}</x-table.td>
                                <x-table.td align="right">
                                    @if ($canExport)
                                        <button type="button" wire:click="downloadPayrollExport({{ $export->id }})" class="page-link">Download</button>
                                    @endif
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="3">
                                <x-empty-state inline title="No payroll exports" description="Exports include approved timesheets within the selected dates." />
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
                    <x-select wire:model.live="guardId" label="Guard *" class="sm:col-span-2">
                        <option value="">Select guard</option>
                        @foreach($guards as $guard)
                            <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                        @endforeach
                    </x-select>
                    <x-input id="timesheet-period-start" wire:model.live="periodStart" label="Period start *" type="date" />
                    <x-input id="timesheet-period-end" wire:model.live="periodEnd" label="Period end *" type="date" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
