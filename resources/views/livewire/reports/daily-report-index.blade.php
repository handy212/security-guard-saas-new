<div>
    <x-page-header title="Daily Activity Reports" description="Review guard shift summaries and approve for clients." />

    <div class="space-y-4 p-6">
        @forelse($reports as $report)
            <x-section-card>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-bold text-slate-900">{{ $report->title }}</h3>
                            <x-badge :status="$report->status" />
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $report->site?->name ?? 'Site' }}
                            · {{ $report->assignedGuard?->full_name ?? 'Guard' }}
                            · {{ $report->report_date?->format('M j, Y') ?? $report->report_date }}
                        </p>
                        @if($report->summary)
                            <p class="mt-3 text-sm text-slate-700">{{ $report->summary }}</p>
                        @endif
                    </div>
                    @if($report->status !== 'approved')
                        <x-button size="sm" wire:click="approve({{ $report->id }})">Approve</x-button>
                    @endif
                </div>
            </x-section-card>
        @empty
            <x-empty-state title="No daily reports" description="Guards submit activity reports from the field." />
        @endforelse
    </div>
</div>
