<div>
    <x-page-shell title="Compliance Dashboard" description="Certifications and documents nearing expiry.">
        <div class="grid grid-cols-4 gap-2">
            <x-stat-card compact label="Expiring certs" :value="$items->count()" icon="guards" :tone="$items->count() ? 'warning' : 'success'" />
            <x-stat-card compact label="Expiring docs" :value="$documents->count()" icon="billing" :tone="$documents->count() ? 'warning' : 'success'" />
            <x-stat-card compact label="Certifications" :value="$certifications->count()" icon="check" />
            <x-stat-card compact label="Window" value="30 days" icon="plan" tone="info" />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-section-card title="Expiring certifications">
                <x-data-table>
                    <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                        <tr>
                            <th class="px-3 py-2">Guard</th>
                            <th class="px-3 py-2">Certification</th>
                            <th class="px-3 py-2">Expires</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr class="table-row-hover">
                                <td class="px-3 py-2">{{ $item->assignedGuard?->full_name ?? '—' }}</td>
                                <td class="px-3 py-2 text-zinc-600">{{ $item->name }}</td>
                                <td class="px-3 py-2 text-xs text-zinc-500">{{ $item->expires_at?->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-3 py-6"><x-empty-state title="No expiring certs" /></td></tr>
                        @endforelse
                    </tbody>
                </x-data-table>
            </x-section-card>

            <x-section-card title="Expiring documents">
                <x-data-table>
                    <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                        <tr>
                            <th class="px-3 py-2">Guard</th>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2">Expires</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $doc)
                            <tr class="table-row-hover">
                                <td class="px-3 py-2">{{ $doc->assignedGuard?->full_name ?? '—' }}</td>
                                <td class="px-3 py-2 text-zinc-600">{{ $doc->type }}</td>
                                <td class="px-3 py-2 text-xs text-zinc-500">{{ $doc->expires_at?->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-3 py-6"><x-empty-state title="No expiring documents" /></td></tr>
                        @endforelse
                    </tbody>
                </x-data-table>
            </x-section-card>
        </div>
    </x-page-shell>
</div>
