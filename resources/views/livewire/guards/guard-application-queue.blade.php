<div>
    <x-page-shell title="Guard applications" description="Review applicants who registered through your public application link.">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('guards.kyg')">Know Your Guard</x-button>
            <x-button :href="route('guards.index')">Roster</x-button>
        </x-slot:actions>

        <x-flash-status />

        @if ($publicApplyUrl)
            <section class="card-surface overflow-hidden">
                <div class="card-header">
                    <div class="min-w-0">
                        <h2 class="card-header-title">Public application link</h2>
                        <p class="card-header-meta truncate font-mono">{{ $publicApplyUrl }}</p>
                    </div>
                    <button
                        type="button"
                        class="btn-secondary shrink-0"
                        x-data
                        x-on:click="navigator.clipboard.writeText(@js($publicApplyUrl)); $el.textContent = 'Copied'; setTimeout(() => $el.textContent = 'Copy link', 1500)"
                    >Copy link</button>
                </div>
            </section>
        @endif

        <x-page-toolbar search="search" searchPlaceholder="Search applicants…">
            <x-slot:tabs>
                <x-segment-control
                    field="statusFilter"
                    :active="$statusFilter"
                    :options="['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All']"
                />
            </x-slot:tabs>
        </x-page-toolbar>

        <div class="space-y-3">
            @forelse($applications as $application)
                <article class="card-surface overflow-hidden" wire:key="app-{{ $application->id }}">
                    <div class="flex flex-col gap-4 p-4 sm:flex-row">
                        <div class="shrink-0">
                            @if ($application->photo_path)
                                <img
                                    src="{{ route('files.application-photo', $application) }}"
                                    alt=""
                                    class="h-28 w-20 rounded-md border border-zinc-200/90 object-cover dark:border-zinc-700"
                                >
                            @else
                                <div class="flex h-28 w-20 items-center justify-center rounded-md border border-dashed border-zinc-300 bg-zinc-50 text-xs text-zinc-400 dark:border-zinc-700 dark:bg-zinc-900">No photo</div>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $application->full_name }}</h3>
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $application->created_at->diffForHumans() }}</p>
                                </div>
                                <x-badge :status="$application->status" />
                            </div>

                            <dl class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                <div class="meta-tile">
                                    <dt class="meta-tile-label">Contact</dt>
                                    <dd class="meta-tile-value font-normal">{{ collect([$application->phone, $application->email])->filter()->implode(' · ') ?: '—' }}</dd>
                                </div>
                                <div class="meta-tile">
                                    <dt class="meta-tile-label">Duty type</dt>
                                    <dd class="meta-tile-value font-normal">{{ $application->dutyTypeLabel() }}</dd>
                                </div>
                                <div class="meta-tile">
                                    <dt class="meta-tile-label">Branch</dt>
                                    <dd class="meta-tile-value font-normal">{{ $application->branch?->name ?? '—' }}</dd>
                                </div>
                            </dl>

                            @if ($application->notes)
                                <div class="meta-tile mt-3">
                                    <p class="meta-tile-label mb-1">Applicant notes</p>
                                    <p class="whitespace-pre-wrap text-sm text-zinc-700 dark:text-zinc-300">{{ $application->notes }}</p>
                                </div>
                            @endif

                            <div class="mt-4 flex flex-wrap gap-2">
                                @if ($application->status === 'pending')
                                    <x-button size="sm" wire:click="approve({{ $application->id }})" wire:confirm="Approve and create guard record?">Approve</x-button>
                                    <x-button size="sm" variant="danger" wire:click="reject({{ $application->id }})" wire:confirm="Reject this application?">Reject</x-button>
                                @elseif ($application->guard_id)
                                    <x-button size="sm" variant="secondary" :href="route('guards.show', $application->guard_id)">View guard</x-button>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <x-empty-state title="No applications" description="Share your public link to start receiving applications.">
                    @if ($publicApplyUrl)
                        <x-slot:actions>
                            <button
                                type="button"
                                class="btn-secondary"
                                x-data
                                x-on:click="navigator.clipboard.writeText(@js($publicApplyUrl))"
                            >Copy application link</button>
                        </x-slot:actions>
                    @endif
                </x-empty-state>
            @endforelse
        </div>

        <x-pagination :paginator="$applications" />
    </x-page-shell>
</div>
