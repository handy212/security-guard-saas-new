<div>
    <x-page-header title="Compliance Dashboard" description="Certifications and documents nearing expiry." />

    <div class="grid gap-4 px-6 pb-4 md:grid-cols-3">
        <x-stat-card label="Expiring certs (30d)" :value="$items->count()" :tone="$items->count() ? 'warning' : 'success'" />
        <x-stat-card label="Expiring docs (30d)" :value="$documents->count()" :tone="$documents->count() ? 'warning' : 'success'" />
        <x-stat-card label="All certifications" :value="$certifications->count()" />
    </div>

    <div class="grid gap-6 px-6 pb-6 lg:grid-cols-2">
        <x-section-card title="Expiring certifications" description="Guard certs due within 30 days.">
            <x-data-table>
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Guard</th>
                        <th class="px-4 py-3">Certification</th>
                        <th class="px-4 py-3">Expires</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr class="table-row-hover">
                            <td class="px-4 py-3 text-slate-900">{{ $item->assignedGuard?->full_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->name }}</td>
                            <td class="px-4 py-3">
                                <x-badge :status="$item->expires_at?->isPast() ? 'past_due' : 'pending'" />
                                <span class="ml-1 text-xs text-slate-500">{{ $item->expires_at?->format('M j, Y') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8"><x-empty-state title="No expiring certs" description="All certifications are current." /></td></tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </x-section-card>

        <x-section-card title="Expiring documents" description="Guard documents due within 30 days.">
            <x-data-table>
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Guard</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Expires</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                        <tr class="table-row-hover">
                            <td class="px-4 py-3 text-slate-900">{{ $doc->assignedGuard?->full_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $doc->type }}</td>
                            <td class="px-4 py-3">
                                <x-badge :status="$doc->expires_at?->isPast() ? 'past_due' : 'pending'" />
                                <span class="ml-1 text-xs text-slate-500">{{ $doc->expires_at?->format('M j, Y') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8"><x-empty-state title="No expiring documents" description="All guard documents are current." /></td></tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </x-section-card>
    </div>
</div>
