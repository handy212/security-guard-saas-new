<div>
    <x-page-header title="Payroll & Accounting Exports" description="Generate timesheets and export data for accounting." />

    <div class="grid gap-4 px-6 pb-4 md:grid-cols-3">
        <x-stat-card label="Timesheets" :value="$timesheets->count()" />
        <x-stat-card label="Exports" :value="$exports->count()" tone="info" />
        <x-stat-card label="Active guards" :value="$guards->count()" tone="success" />
    </div>

    <div class="space-y-6 p-6 pt-0">
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
                <p class="mb-4 text-sm text-slate-600">Export approved invoices for the current tenant to a CSV file.</p>
                <x-button wire:click="exportInvoices">Export invoices CSV</x-button>
            </x-form-card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-data-table title="Recent timesheets">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Guard</th>
                        <th class="px-4 py-3">Period</th>
                        <th class="px-4 py-3">Hours</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($timesheets as $sheet)
                        <tr class="table-row-hover">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $sheet->assignedGuard?->full_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $sheet->period_start?->format('M j') }} – {{ $sheet->period_end?->format('M j, Y') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $sheet->regular_hours }}h + {{ $sheet->overtime_hours }}h OT</td>
                            <td class="px-4 py-3"><x-badge :status="$sheet->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10"><x-empty-state title="No timesheets" description="Generate a timesheet above." /></td></tr>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-data-table title="Recent exports">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">File</th>
                        <th class="px-4 py-3">Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exports as $export)
                        <tr class="table-row-hover">
                            <td class="px-4 py-3 text-slate-900">{{ $export->export_type ?? 'CSV' }}</td>
                            <td class="px-4 py-3 text-slate-600 truncate max-w-xs">{{ $export->file_path ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $export->created_at?->format('M j, H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-10"><x-empty-state title="No exports" description="Run an accounting export above." /></td></tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </div>
    </div>
</div>
