<div>
    <x-page-shell title="Guard applications" description="Review applicants who registered through your public application link.">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('guards.kyg')">Know Your Guard</x-button>
            <x-button :href="route('guards.index')">Roster</x-button>
        </x-slot:actions>

        <x-flash-status />

        @if ($publicApplyUrl)
            <section class="card-surface mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-zinc-900">Public application link</h2>
                </div>
                <button
                    type="button"
                    class="btn-secondary shrink-0"
                    x-data
                    x-on:click="navigator.clipboard.writeText(@js($publicApplyUrl)); $el.textContent = 'Copied'; setTimeout(() => $el.textContent = 'Copy link', 1500)"
                >Copy link</button>
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
                <article class="card-surface p-4" wire:key="app-{{ $application->id }}">
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <div class="shrink-0">
                            @if ($application->photo_path)
                                <img
                                    src="{{ route('files.application-photo', $application) }}"
                                    alt=""
                                    class="h-28 w-20 rounded-xl border border-zinc-200 object-cover"
                                >
                            @else
                                <div class="flex h-28 w-20 items-center justify-center rounded-xl border border-dashed border-zinc-300 bg-zinc-50 text-xs text-zinc-400">No photo</div>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <h3 class="text-base font-semibold text-zinc-900">{{ $application->full_name }}</h3>
                                    <p class="mt-0.5 text-xs text-zinc-500">{{ $application->created_at->diffForHumans() }}</p>
                                </div>
                                <x-badge :status="$application->status" />
                            </div>

                            <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <dt class="text-xs text-zinc-500">Contact</dt>
                                    <dd class="text-zinc-800">{{ collect([$application->phone, $application->email])->filter()->implode(' · ') ?: '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-zinc-500">Duty type</dt>
                                    <dd class="text-zinc-800">{{ $application->dutyTypeLabel() }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-zinc-500">Branch</dt>
                                    <dd class="text-zinc-800">{{ $application->branch?->name ?? '—' }}</dd>
                                </div>
                            </dl>

                            @if ($application->notes)
                                <div class="mt-3 rounded-lg bg-zinc-50 p-3 text-sm text-zinc-700">
                                    <p class="mb-1 text-xs font-medium uppercase tracking-wide text-zinc-500">Applicant notes</p>
                                    <p class="whitespace-pre-wrap">{{ $application->notes }}</p>
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
                <x-empty-state title="No applications" description="Share your public link to start receiving applications." />
            @endforelse
        </div>

        <x-pagination :paginator="$applications" />
    </x-page-shell>
</div>
