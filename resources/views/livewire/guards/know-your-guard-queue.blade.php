<div>
    <x-page-shell title="Know Your Guard" description="Guards awaiting vetting and verification.">
        <x-page-toolbar search="search" searchPlaceholder="Search pending guards…" />

        <x-data-table>
            <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                <tr>
                    <th class="px-3 py-2">Guard</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2">KYG</th>
                    <th class="px-3 py-2 text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($guards as $guard)
                    <tr class="table-row-hover">
                        <td class="px-3 py-2 font-medium text-zinc-900">{{ $guard->full_name }}</td>
                        <td class="px-3 py-2"><x-badge :status="$guard->status" /></td>
                        <td class="px-3 py-2"><x-badge :status="$guard->verification_status" /></td>
                        <td class="px-3 py-2 text-right">
                            <a href="{{ route('guards.show', $guard) }}?tab=verification" class="btn-link">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-8"><x-empty-state title="All clear" description="No guards pending Know Your Guard verification." /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>

        {{ $guards->links('components.pagination') }}
    </x-page-shell>
</div>
