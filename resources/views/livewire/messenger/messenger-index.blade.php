<div>
    <x-page-shell title="Messenger" description="Secure site threads between guards and back-office staff.">
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

        <div class="page-board min-h-[28rem]">
            <x-section-card title="Threads" class="flex flex-col">
                <div class="-mx-1 flex-1 overflow-y-auto px-1">
                    @forelse($threads as $thread)
                        @php $latest = $thread->messages->first(); @endphp
                        <button
                            type="button"
                            wire:click="selectThread({{ $thread->id }})"
                            class="w-full rounded-lg border border-transparent px-2 py-3 text-left text-sm transition hover:border-zinc-200 hover:bg-zinc-50 {{ $activeThreadId === $thread->id ? 'border-accent-200 bg-accent-50' : '' }}"
                            wire:key="thread-{{ $thread->id }}"
                        >
                            <div class="font-semibold text-zinc-900">{{ $thread->subject }}</div>
                            <div class="text-xs text-zinc-500">{{ $thread->site?->name ?? '—' }} · {{ $thread->messages_count }} messages</div>
                            @if ($latest)
                                <div class="mt-1 truncate text-xs text-zinc-600">{{ $latest->body }}</div>
                                <div class="text-[10px] text-zinc-400">{{ $latest->created_at?->diffForHumans() }}</div>
                            @endif
                        </button>
                    @empty
                        <x-empty-state compact title="No threads" description="Create a site thread to start messaging." />
                    @endforelse
                </div>
            </x-section-card>

            <x-section-card
                :title="$activeThread ? $activeThread->subject : 'Messages'"
                class="flex flex-col"
            >
                @if($activeThread)
                    <div class="mb-3 flex flex-wrap gap-2 text-xs text-zinc-500">
                        <span>{{ $activeThread->site?->name }}</span>
                        <span>·</span>
                        <span>{{ $activeThread->participants->count() }} participants</span>
                        <span>·</span>
                        <span>{{ $activeThread->participants->pluck('user.name')->filter()->take(4)->implode(', ') }}{{ $activeThread->participants->count() > 4 ? '…' : '' }}</span>
                    </div>

                    <div class="mb-4 max-h-96 flex-1 space-y-3 overflow-y-auto rounded-lg border border-zinc-100 bg-zinc-50/50 p-3">
                        @forelse($activeThread->messages as $message)
                            <div class="rounded-lg bg-white p-3 text-sm shadow-sm" wire:key="msg-{{ $message->id }}">
                                <div class="mb-1 flex items-center justify-between gap-2">
                                    <span class="text-xs font-semibold text-zinc-700">{{ $message->user?->name ?? 'System' }}</span>
                                    <span class="text-[10px] text-zinc-400">{{ $message->created_at?->format('M j, H:i') }}</span>
                                </div>
                                <div class="whitespace-pre-wrap text-zinc-800">{{ $message->body }}</div>
                                @if ($message->attachment_path)
                                    <button type="button" class="mt-2 text-xs font-medium text-accent-600 hover:underline" wire:click="downloadAttachment({{ $message->id }})">Download attachment</button>
                                @endif
                            </div>
                        @empty
                            <x-empty-state compact title="No messages yet" description="Send the first message in this thread." />
                        @endforelse
                    </div>

                    <form wire:submit="send" class="space-y-2 border-t border-zinc-100 pt-3">
                        <x-textarea wire:model="newMessage" placeholder="Type a message…" rows="2" />
                        <div class="flex flex-wrap items-end gap-2">
                            <x-file-input wire:model="attachmentFile" label="Attachment (optional)" class="min-w-0 flex-1" />
                            <x-button type="submit">Send</x-button>
                        </div>
                        @error('newMessage') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </form>
                @else
                    <div class="flex flex-1 items-center justify-center py-10">
                        <x-empty-state compact title="Select a thread" description="Choose a conversation or create a new one." />
                    </div>
                @endif
            </x-section-card>
        </div>
    </x-page-shell>

    @if ($showForm)
        <x-drawer title="New thread" width="md">
            <x-drawer-form wire:submit="createThread" submit-label="Create thread">
                <x-input wire:model="threadForm.subject" label="Subject" class="sm:col-span-2" />
                <x-select wire:model="threadForm.site_id" label="Site" class="sm:col-span-2">
                    <option value="">Select site</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </x-select>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-zinc-700">Participants</label>
                    <div class="max-h-48 space-y-1 overflow-y-auto rounded-lg border border-zinc-200 p-2">
                        @foreach($staff as $user)
                            <label class="flex items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-zinc-50">
                                <input type="checkbox" wire:model="threadForm.participant_ids" value="{{ $user->id }}" class="rounded border-zinc-300">
                                {{ $user->name }}
                            </label>
                        @endforeach
                    </div>
                    @error('threadForm.participant_ids') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
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
