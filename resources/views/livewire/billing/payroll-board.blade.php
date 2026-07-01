<div>
    <x-page-shell title="Payroll & Accounting Exports" description="Generate timesheets and export data for accounting.">
        <div class="grid grid-cols-4 gap-2">
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
                <x-button wire:click="exportInvoices">Export invoices CSV</x-button>
            </x-form-card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-data-table title="Recent timesheets">
                <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                    <tr>
                        <th class="px-3 py-2">Guard</th>
                        <th class="px-3 py-2">Period</th>
                        <th class="px-3 py-2">Hours</th>
                        <th class="px-3 py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($timesheets as $sheet)
                        <tr class="table-row-hover">
                            <td class="px-3 py-2 font-medium">{{ $sheet->assignedGuard?->full_name ?? '—' }}</td>
                            <td class="px-3 py-2 text-zinc-600">{{ $sheet->period_start?->format('M j') }} – {{ $sheet->period_end?->format('M j, Y') }}</td>
                            <td class="px-3 py-2 text-zinc-600">{{ $sheet->regular_hours }}h + {{ $sheet->overtime_hours }}h OT</td>
                            <td class="px-3 py-2"><x-badge :status="$sheet->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-8"><x-empty-state title="No timesheets" /></td></tr>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-data-table title="Recent exports">
                <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                    <tr>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">File</th>
                        <th class="px-3 py-2">Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exports as $export)
                        <tr class="table-row-hover">
                            <td class="px-3 py-2">{{ $export->export_type ?? 'CSV' }}</td>
                            <td class="max-w-xs truncate px-3 py-2 text-zinc-600">{{ $export->file_path ?? '—' }}</td>
                            <td class="px-3 py-2 text-zinc-600">{{ $export->created_at?->format('M j, H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-8"><x-empty-state title="No exports" /></td></tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </div>
    </x-page-shell>
</div>
