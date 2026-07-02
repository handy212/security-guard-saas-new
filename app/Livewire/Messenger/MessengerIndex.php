<?php

namespace App\Livewire\Messenger;

use App\Models\MessageThread;
use App\Models\Site;
use App\Services\MessengerService;
use App\Support\TenantContext;
use Livewire\Component;

class MessengerIndex extends Component
{
    public ?int $activeThreadId = null;

    public string $newMessage = '';

    public string $newSubject = '';

    public ?int $newSiteId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('dispatch.manage'), 403);
        $this->activeThreadId = MessageThread::where('tenant_id', TenantContext::id())->latest()->value('id');
    }

    public function send(MessengerService $messenger): void
    {
        $this->validate(['newMessage' => 'required|string|max:5000']);
        $thread = MessageThread::findOrFail($this->activeThreadId);
        $messenger->sendMessage($thread, auth()->id(), $this->newMessage);
        $this->newMessage = '';
    }

    public function createThread(MessengerService $messenger): void
    {
        $this->validate(['newSubject' => 'required', 'newSiteId' => 'required']);
        $thread = $messenger->createThread($this->newSiteId, $this->newSubject, [auth()->id()]);
        $this->activeThreadId = $thread->id;
        $this->newSubject = '';
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $threads = MessageThread::with(['site', 'messages.user'])->where('tenant_id', $tenantId)->latest()->get();
        $activeThread = $threads->firstWhere('id', $this->activeThreadId);

        return view('livewire.messenger.messenger-index', [
            'threads' => $threads,
            'activeThread' => $activeThread,
            'sites' => Site::where('tenant_id', $tenantId)->orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
