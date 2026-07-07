<div>
    <x-page-shell title="Messenger" description="Secure guard and back-office communication.">
        <div class="stat-grid">
            <x-stat-card compact label="Threads" :value="$threads->count()" icon="users" />
            <x-stat-card compact label="Sites" :value="$sites->count()" icon="sites" tone="info" />
            <x-stat-card compact label="Messages" :value="$activeThread?->messages->count() ?? 0" icon="plan" />
            <x-stat-card compact label="Active" :value="$activeThread ? 'Selected' : 'None'" icon="check" :tone="$activeThread ? 'success' : 'default'" />
        </div>

        <div class="page-board">
            <x-section-card title="Threads" class="flex min-h-[24rem] flex-col">
                <div class="mb-3 space-y-2">
                    <x-input wire:model="newSubject" label="New thread subject" />
                    <x-select wire:model="newSiteId" label="Site">
                        <option value="">Select site</option>
                        @foreach($sites as $site)<option value="{{ $site->id }}">{{ $site->name }}</option>@endforeach
                    </x-select>
                    <x-button size="sm" wire:click="createThread">Create</x-button>
                </div>
                @foreach($threads as $thread)
                    <button wire:click="$set('activeThreadId', {{ $thread->id }})" class="block w-full border-t py-2 text-left text-sm first:border-0 transition hover:bg-zinc-50 {{ $activeThreadId === $thread->id ? 'rounded-md bg-accent-50 font-semibold text-accent-600' : '' }}" wire:key="thread-{{ $thread->id }}">
                        {{ $thread->subject }}
                        <div class="text-xs text-zinc-400">{{ $thread->site?->name }}</div>
                    </button>
                @endforeach
            </x-section-card>

            <x-section-card title="Messages" class="flex min-h-[24rem] flex-col">
                @if($activeThread)
                    <div class="mb-4 max-h-80 space-y-2 overflow-y-auto">
                        @foreach($activeThread->messages as $message)
                            <div class="rounded-lg bg-zinc-50 p-2 text-sm">
                                <div class="text-xs font-medium text-zinc-600">{{ $message->user?->name }}</div>
                                <div>{{ $message->body }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex gap-2">
                        <x-textarea wire:model="newMessage" placeholder="Type a message..." class="flex-1" rows="2" />
                        <x-button wire:click="send">Send</x-button>
                    </div>
                @else
                    <x-empty-state title="Select a thread" />
                @endif
            </x-section-card>
        </div>
    </x-page-shell>
</div>

@script
<script>
    if (window.Echo && @json($activeThread?->id) && @json(auth()->user()?->tenant_id)) {
        window.Echo.channel('tenant.{{ auth()->user()->tenant_id }}.messenger.{{ $activeThread?->id }}')
            .listen('.message.sent', () => $wire.$refresh());
    }
</script>
@endscript
