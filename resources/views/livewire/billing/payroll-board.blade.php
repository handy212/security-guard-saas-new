<div>
    <x-page-shell title="Payroll" description="Generate guard timesheets from attendance and export payroll CSV.">
        <x-flash-status type="success" />

        <div class="stat-grid">
            <x-stat-card compact label="Timesheets" :value="$timesheets->count()" icon="plan" />
            <x-stat-card compact label="Active guards" :value="$guards->count()" icon="guards" tone="success" />
            <x-stat-card compact label="Payroll exports" :value="$payrollExports->count()" icon="billing" tone="info" />
            <x-stat-card compact label="Period" :value="$periodStart ? \Carbon\Carbon::parse($periodStart)->format('M Y') : '—'" icon="schedules" />
        </div>

        <div class="page-grid-2">
            <x-form-card title="Generate timesheet" description="Build a payroll timesheet from attendance for a guard.">
                <form wire:submit="generateTimesheet" class="space-y-3">
                    <x-select wire:model="guardId" label="Guard">
                        <option value="">Select guard</option>
                        @foreach($guards as $guard)
                            <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                        @endforeach
                    </x-select>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-input wire:model="periodStart" label="Period start" type="date" />
                        <x-input wire:model="periodEnd" label="Period end" type="date" />
                    </div>
                    <x-button type="submit" size="sm">Generate timesheet</x-button>
                </form>
            </x-form-card>

            @if ($canExport)
                <x-form-card title="Payroll export" description="CSV from completed shifts for QuickBooks import.">
                    <x-button wire:click="exportQuickBooks" size="sm">Export payroll CSV</x-button>
                </x-form-card>
            @endif
        </div>

        <div class="page-grid-2">
            <x-data-table title="Recent timesheets">
                <x-table.head>
                    <tr>
                        <x-table.th>Guard</x-table.th>
                        <x-table.th>Period</x-table.th>
                        <x-table.th>Hours</x-table.th>
                        <x-table.th>Status</x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse($timesheets as $sheet)
                        <tr class="table-row-hover" wire:key="timesheet-{{ $sheet->id }}">
                            <x-table.td><span class="font-medium text-zinc-900">{{ $sheet->assignedGuard?->full_name ?? '—' }}</span></x-table.td>
                            <x-table.td muted>{{ $sheet->period_start?->format('M j') }} – {{ $sheet->period_end?->format('M j, Y') }}</x-table.td>
                            <x-table.td muted>{{ $sheet->regular_hours }}h + {{ $sheet->overtime_hours }}h OT</x-table.td>
                            <x-table.td><x-badge :status="$sheet->status" /></x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="4"><x-empty-state compact title="No timesheets" description="Generate a timesheet from attendance logs." /></x-table.empty>
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
                        <x-table.empty colspan="3"><x-empty-state compact title="No payroll exports" /></x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>
        </div>
    </x-page-shell>
</div>
