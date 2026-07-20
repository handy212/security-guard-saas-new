<div>
    <x-page-shell
        title="Messenger"
        description="Secure site threads between guards and back-office staff."
        :breadcrumbs="[['label' => 'Messenger']]"
    >
        <x-slot:actions>
            <x-button wire:click="openCreateThread">New thread</x-button>
        </x-slot:actions>

        <x-flash-status />

        <div class="stat-grid">
            <x-stat-card compact label="Threads" :value="$stats['threads']" icon="users" />
            <x-stat-card compact label="Messages" :value="$stats['messages']" icon="plan" tone="info" />
            <x-stat-card compact label="Sites in use" :value="$stats['sites']" icon="sites" />
            <x-stat-card compact label="Participants" :value="$stats['participants']" icon="guards" :tone="$stats['participants'] ? 'success' : 'default'" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search threads…" />

        <div class="page-board min-h-[32rem]">
            <x-section-card title="Threads" :description="$threads->count().' conversation'.($threads->count() === 1 ? '' : 's')" flush class="flex flex-col">
                <div class="flex-1 overflow-y-auto px-2 py-2">
                    @forelse($threads as $thread)
                        @php $latest = $thread->messages->first(); @endphp
                        <button
                            type="button"
                            wire:click="selectThread({{ $thread->id }})"
                            @class([
                                'board-item border-l-accent-500',
                                'board-item-active' => $activeThreadId === $thread->id,
                            ])
                            wire:key="thread-{{ $thread->id }}"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="board-item-title min-w-0 truncate">{{ $thread->subject }}</div>
                                @if ($latest?->created_at)
                                    <span class="shrink-0 tabular-nums text-[10px] text-zinc-400">{{ $latest->created_at->diffForHumans() }}</span>
                                @endif
                            </div>
                            <div class="board-item-meta">{{ $thread->site?->name ?? '—' }} · <span class="tabular-nums">{{ $thread->messages_count }}</span> msg</div>
                            @if ($latest)
                                <div class="mt-1 truncate text-xs text-zinc-600 dark:text-zinc-300">{{ $latest->body }}</div>
                            @endif
                        </button>
                    @empty
                        <x-empty-state compact title="No threads" description="Create a site thread to start messaging.">
                            <x-slot:actions>
                                <x-button size="sm" wire:click="openCreateThread">New thread</x-button>
                            </x-slot:actions>
                        </x-empty-state>
                    @endforelse
                </div>
            </x-section-card>

            <section class="card-surface flex min-h-[28rem] flex-col overflow-hidden">
                @if ($activeThread)
                    <div class="card-header shrink-0">
                        <div class="min-w-0 flex-1">
                            <h2 class="card-header-title">{{ $activeThread->subject }}</h2>
                            <p class="card-header-meta">
                                {{ $activeThread->site?->name ?? 'No site' }}
                                · {{ $activeThread->participants->count() }} participant{{ $activeThread->participants->count() === 1 ? '' : 's' }}
                            </p>
                        </div>
                        <x-button size="sm" variant="secondary" wire:click="openCreateThread">New thread</x-button>
                    </div>

                    @if ($activeThread->participants->isNotEmpty())
                        <div class="flex flex-wrap gap-1.5 border-b border-zinc-100 px-4 py-2.5 dark:border-zinc-800">
                            @foreach ($activeThread->participants->take(8) as $participant)
                                <span class="guard-chip">{{ $participant->user?->name ?? 'User' }}</span>
                            @endforeach
                            @if ($activeThread->participants->count() > 8)
                                <span class="status-chip status-chip-neutral">+{{ $activeThread->participants->count() - 8 }} more</span>
                            @endif
                        </div>
                    @endif

                    <div class="chat-pane">
                        <div class="chat-messages" wire:key="thread-messages-{{ $activeThread->id }}">
                            @forelse($activeThread->messages as $message)
                                @php $mine = (int) $message->user_id === (int) auth()->id(); @endphp
                                <div @class(['chat-bubble', $mine ? 'chat-bubble-mine' : 'chat-bubble-other']) wire:key="msg-{{ $message->id }}">
                                    <div class="chat-bubble-meta">
                                        <span class="chat-bubble-author">{{ $mine ? 'You' : ($message->user?->name ?? 'System') }}</span>
                                        <span class="chat-bubble-time">{{ $message->created_at?->format('M j, H:i') }}</span>
                                    </div>
                                    <div class="whitespace-pre-wrap leading-relaxed">{{ $message->body }}</div>
                                    @if ($message->attachment_path)
                                        <button type="button" class="page-link mt-2" wire:click="downloadAttachment({{ $message->id }})">Download attachment</button>
                                    @endif
                                </div>
                            @empty
                                <div class="flex h-full items-center justify-center py-8">
                                    <x-empty-state compact title="No messages yet" description="Send the first message in this thread." />
                                </div>
                            @endforelse
                        </div>

                        <form wire:submit="send" class="chat-compose">
                            <x-textarea wire:model="newMessage" placeholder="Type a message…" rows="2" />
                            <div class="flex flex-wrap items-end gap-2">
                                <x-file-input wire:model="attachmentFile" label="Attachment (optional)" class="min-w-0 flex-1" />
                                <x-button type="submit" wire:loading.attr="disabled" wire:target="send">
                                    <span wire:loading.remove wire:target="send">Send</span>
                                    <span wire:loading wire:target="send">Sending…</span>
                                </x-button>
                            </div>
                            @error('newMessage') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            @error('attachmentFile') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </form>
                    </div>
                @else
                    <div class="flex flex-1 items-center justify-center p-6">
                        <x-empty-state title="Select a thread" description="Choose a conversation or create a new one.">
                            <x-slot:actions>
                                <x-button size="sm" wire:click="openCreateThread">New thread</x-button>
                            </x-slot:actions>
                        </x-empty-state>
                    </div>
                @endif
            </section>
        </div>
    </x-page-shell>

    @if ($showForm)
        <x-drawer title="New thread" description="Start a site conversation with back-office participants." width="md" close-method="closeDrawer">
            <x-drawer-form wire:submit="createThread" submit-label="Create thread" close-method="closeDrawer" target="createThread">
                <x-form-section title="Thread">
                    <x-input wire:model="threadForm.subject" label="Subject *" class="sm:col-span-2" />
                    <x-select wire:model="threadForm.site_id" label="Site *" class="sm:col-span-2">
                        <option value="">Select site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                </x-form-section>
                <x-form-section title="Participants" description="Select who should receive this thread.">
                    <div class="sm:col-span-2 max-h-48 space-y-0.5 overflow-y-auto rounded-md border border-zinc-200/90 p-1.5 dark:border-zinc-700">
                        @forelse($staff as $user)
                            <label class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-zinc-700 transition hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-zinc-800/60">
                                <input type="checkbox" wire:model="threadForm.participant_ids" value="{{ $user->id }}" class="rounded border-zinc-300 text-accent-600 focus:ring-accent-600/20 dark:border-zinc-600">
                                {{ $user->name }}
                            </label>
                        @empty
                            <p class="px-2 py-3 text-xs text-zinc-500">No staff users available.</p>
                        @endforelse
                    </div>
                    @error('threadForm.participant_ids') <p class="sm:col-span-2 form-error">{{ $message }}</p> @enderror
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>

@script
<script>
    if (window.Echo && @json($activeThread?->id) && @json(auth()->user()?->tenant_id)) {
        window.Echo.channel('tenant.{{ auth()->user()->tenant_id }}.messenger.{{ $activeThread?->id }}')
            .listen('.message.sent', () => $wire.$refresh());
    }
</script>
@endscript
