<div>
    <x-page-shell title="Payroll & Accounting Exports" description="Generate timesheets and export data for accounting.">
        <div class="stat-grid">
            <x-stat-card compact label="Exports" :value="$exports->count()" icon="billing" tone="info" />
            <x-stat-card compact label="Active guards" :value="$guards->count()" icon="guards" tone="success" />
            <x-stat-card compact label="Timesheets" :value="$timesheets->count()" icon="plan" />
            <x-stat-card compact label="Period" :value="$periodStart ? \Carbon\Carbon::parse($periodStart)->format('M Y') : '—'" icon="schedules" />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
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
                    <x-button type="submit">Generate timesheet</x-button>
                </form>
            </x-form-card>

            <x-form-card title="Accounting export" description="Download invoice data as CSV for QuickBooks or Xero.">
                <p class="mb-4 text-sm text-zinc-600">Export approved invoices for the current tenant to a CSV file.</p>
                <div class="flex flex-wrap gap-2">
                    <x-button wire:click="exportInvoices">Export invoices CSV</x-button>
                    <x-button variant="secondary" wire:click="exportQuickBooks">Export QuickBooks payroll</x-button>
                </div>
            </x-form-card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
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
                        <x-table.empty colspan="4"><x-empty-state compact title="No timesheets" /></x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-data-table title="Recent exports">
                <x-table.head>
                    <tr>
                        <x-table.th>Type</x-table.th>
                        <x-table.th>File</x-table.th>
                        <x-table.th>Created</x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse($exports as $export)
                        <tr class="table-row-hover" wire:key="export-{{ $export->id }}">
                            <x-table.td>{{ $export->export_type ?? 'CSV' }}</x-table.td>
                            <x-table.td muted class="max-w-xs truncate">{{ $export->file_path ?? '—' }}</x-table.td>
                            <x-table.td muted>{{ $export->created_at?->format('M j, H:i') }}</x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="3"><x-empty-state compact title="No exports" /></x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-data-table title="Payroll exports (QuickBooks)">
                <x-table.head>
                    <tr>
                        <x-table.th>Provider</x-table.th>
                        <x-table.th>Period</x-table.th>
                        <x-table.th>Exported</x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse($payrollExports as $export)
                        <tr class="table-row-hover" wire:key="payroll-export-{{ $export->id }}">
                            <x-table.td>{{ $export->provider }}</x-table.td>
                            <x-table.td muted>{{ $export->period_start?->format('M j') }} – {{ $export->period_end?->format('M j') }}</x-table.td>
                            <x-table.td muted>{{ $export->exported_at?->format('M j, H:i') }}</x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="3"><x-empty-state compact title="No payroll exports" /></x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>
        </div>
    </x-page-shell>
</div>
