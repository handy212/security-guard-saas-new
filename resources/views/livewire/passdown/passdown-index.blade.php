<div>
    <x-page-shell
        title="Passdown Logs"
        description="Shift handoff notes between guards on the same post."
        :breadcrumbs="[['label' => 'Passdown']]"
    >
        <x-slot:actions>
            <x-button wire:click="openForm">New passdown</x-button>
        </x-slot:actions>

        <x-flash-status />

        <div class="stat-grid">
            <x-stat-card compact label="Recent logs" :value="$stats['total']" icon="plan" />
            <x-stat-card compact label="Sites covered" :value="$stats['sites']" icon="sites" tone="info" />
            <x-stat-card compact label="Today" :value="$stats['today']" icon="check" tone="success" />
            <x-stat-card compact label="Sites available" :value="$sites->count()" icon="schedules" />
        </div>

        <x-section-card title="Recent passdowns" :description="$logs->isEmpty() ? 'No handoffs yet' : 'Latest 30 handoff notes'" flush>
            @forelse($logs as $log)
                <div class="list-row-start" wire:key="passdown-{{ $log->id }}">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $log->site?->name }}</span>
                            @if($log->sitePost)
                                <span class="status-chip status-chip-neutral">{{ $log->sitePost->name }}</span>
                            @endif
                        </div>
                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $log->assignedGuard?->full_name ?? 'Staff' }}
                            · <span class="tabular-nums">{{ $log->created_at?->format('M j, H:i') }}</span>
                            · {{ $log->created_at?->diffForHumans() }}
                        </div>
                        <p class="mt-1.5 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $log->content }}</p>
                    </div>
                    <div class="table-inline-actions shrink-0">
                        <button type="button" wire:click="edit({{ $log->id }})" class="table-action">Edit</button>
                        <button type="button" wire:click="delete({{ $log->id }})" wire:confirm="Delete this passdown?" class="table-action-danger">Delete</button>
                    </div>
                </div>
            @empty
                <div class="p-3">
                    <x-empty-state title="No passdown logs" description="Handoff notes appear here after shifts or when you log one.">
                        <x-slot:actions>
                            <x-button size="sm" wire:click="openForm">New passdown</x-button>
                        </x-slot:actions>
                    </x-empty-state>
                </div>
            @endforelse
        </x-section-card>
    </x-page-shell>

    @if ($showForm)
        <x-drawer
            :title="$editingId ? 'Edit passdown' : 'New passdown'"
            :description="$editingId ? 'Update this handoff note.' : 'Capture what the next shift needs to know.'"
            width="md"
            close-method="closeDrawer"
        >
            <x-drawer-form wire:submit.prevent="save" :submit-label="$editingId ? 'Save changes' : 'Save passdown'" close-method="closeDrawer" target="save">
                <x-form-section title="Location">
                    <x-select wire:model="form.site_id" label="Site *" class="sm:col-span-2">
                        <option value="">Select</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model="form.site_post_id" label="Post (optional)" class="sm:col-span-2">
                        <option value="">Any</option>
                        @foreach($posts as $post)
                            <option value="{{ $post->id }}">{{ $post->name }}</option>
                        @endforeach
                    </x-select>
                </x-form-section>
                <x-form-section title="Handoff">
                    <x-textarea wire:model="form.content" label="Notes *" class="sm:col-span-2" rows="5" hint="At least 10 characters" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
