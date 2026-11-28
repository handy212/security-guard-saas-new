<div>
    <x-portal-page-header
        :title="$report->title ?: 'Daily activity report'"
        :description="$report->site?->name ?: 'Approved service report'"
    >
        <x-slot:actions>
            <x-button size="sm" variant="secondary" wire:click="download">Download PDF</x-button>
            <x-button variant="secondary" :href="route('client-portal.dashboard')">Back to overview</x-button>
        </x-slot:actions>
    </x-portal-page-header>

    <div class="mx-auto max-w-4xl space-y-4">
        <x-section-card title="Service details">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Site</dt>
                    <dd class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $report->site?->name ?? 'Not specified' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Service period</dt>
                    <dd class="mt-1 text-sm tabular-nums text-zinc-900 dark:text-zinc-100">
                        @if ($assignment?->shift?->starts_at)
                            {{ $assignment->shift->starts_at->format('M j, Y g:i A') }}
                            @if ($assignment->shift->ends_at)
                                – {{ $assignment->shift->ends_at->format('g:i A') }}
                            @endif
                        @else
                            {{ $report->report_date?->format('M j, Y') ?? 'Not specified' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Report status</dt>
                    <dd class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">Approved</dd>
                </div>
            </dl>
        </x-section-card>

        <x-section-card title="Shift summary">
            @if (filled($report->summary))
                <p class="whitespace-pre-line text-sm leading-6 text-zinc-700 dark:text-zinc-200">{{ $report->summary }}</p>
            @else
                <p class="text-sm text-zinc-500 dark:text-zinc-400" role="status">No service summary was included.</p>
            @endif
        </x-section-card>

        <x-section-card title="Patrol evidence">
            @forelse ($patrols as $patrol)
                <article class="border-b border-zinc-100 py-4 last:border-b-0 dark:border-zinc-800" wire:key="portal-patrol-{{ $patrol->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $patrol->route?->name ?? 'Patrol' }}</h3>
                            <p class="mt-1 text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                                {{ ucfirst(str_replace('_', ' ', $patrol->status)) }}
                                @if ($patrol->started_at)
                                    · Started {{ $patrol->started_at->format('M j, Y g:i A') }}
                                @endif
                            </p>
                        </div>
                        <span class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ $patrol->scans->count() }} checkpoint scans</span>
                    </div>

                    @forelse ($patrol->scans as $scan)
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-sm" wire:key="portal-scan-{{ $scan->id }}">
                            <span class="text-zinc-700 dark:text-zinc-200">{{ $scan->checkpoint?->name ?? 'Checkpoint' }}</span>
                            <span class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                                {{ $scan->scanned_at?->format('M j, Y g:i A') }}
                                · {{ ucfirst(str_replace('_', ' ', $scan->status)) }}
                            </span>
                        </div>
                    @empty
                        <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400" role="status">No checkpoint scans were recorded.</p>
                    @endforelse
                </article>
            @empty
                <div class="p-3">
                    <x-empty-state title="No patrol evidence linked" description="No patrol sessions were recorded for this shift." />
                </div>
            @endforelse
        </x-section-card>
    </div>
</div>
