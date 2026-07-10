<?php

namespace App\Livewire\Messenger;

use App\Livewire\Concerns\HasFormDrawer;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Site;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\MessengerService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessengerIndex extends Component
{
    use HasFormDrawer, WithFileUploads;

    public ?int $activeThreadId = null;

    public string $newMessage = '';

    public string $search = '';

    public $attachmentFile;

    public array $threadForm = [
        'subject' => '',
        'site_id' => '',
        'participant_ids' => [],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('dispatch.manage'), 403);
        $this->activeThreadId = MessageThread::where('tenant_id', TenantContext::id())->latest()->value('id');
    }

    public function selectThread(int $id): void
    {
        $thread = MessageThread::findOrFail($id);
        abort_unless((int) $thread->tenant_id === (int) TenantContext::id(), 404);
        $this->activeThreadId = $thread->id;
    }

    public function openCreateThread(): void
    {
        $this->threadForm = [
            'subject' => '',
            'site_id' => '',
            'participant_ids' => [auth()->id()],
        ];
        $this->showForm = true;
    }

    public function createThread(MessengerService $messenger): void
    {
        $data = $this->validate([
            'threadForm.subject' => 'required|string|max:255',
            'threadForm.site_id' => 'required|exists:sites,id',
            'threadForm.participant_ids' => 'required|array|min:1',
            'threadForm.participant_ids.*' => 'integer|exists:users,id',
        ])['threadForm'];

        $participantIds = collect($data['participant_ids'])
            ->map(fn ($id) => (int) $id)
            ->push(auth()->id())
            ->unique()
            ->values()
            ->all();

        $thread = $messenger->createThread((int) $data['site_id'], $data['subject'], $participantIds);
        $this->activeThreadId = $thread->id;
        $this->closeDrawer();
        session()->flash('status', 'Thread created.');
    }

    public function send(MessengerService $messenger, FileUploadService $uploads): void
    {
        $this->validate([
            'newMessage' => 'required_without:attachmentFile|nullable|string|max:5000',
            'attachmentFile' => 'nullable|file|max:10240',
        ]);

        abort_unless($this->activeThreadId, 422);
        $thread = MessageThread::findOrFail($this->activeThreadId);

        $path = null;
        if ($this->attachmentFile) {
            $path = $uploads->storeMessageAttachment(TenantContext::id(), $thread->id, $this->attachmentFile);
        }

        $body = trim($this->newMessage) !== '' ? $this->newMessage : 'Attachment';
        $messenger->sendMessage($thread, auth()->id(), $body, $path);
        $this->reset(['newMessage', 'attachmentFile']);
    }

    public function downloadAttachment(int $messageId): StreamedResponse
    {
        $message = Message::with('thread')->findOrFail($messageId);
        abort_unless((int) $message->thread?->tenant_id === (int) TenantContext::id(), 404);
        abort_unless($message->attachment_path && Storage::disk('public')->exists($message->attachment_path), 404);

        return Storage::disk('public')->download($message->attachment_path);
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $threads = MessageThread::with(['site', 'participants.user', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->withCount('messages')
            ->where('tenant_id', $tenantId)
            ->when($this->search !== '', fn ($q) => $q->where('subject', 'like', '%'.$this->search.'%'))
            ->latest()
            ->get();

        $activeThread = $this->activeThreadId
            ? MessageThread::with(['site', 'participants.user', 'messages.user'])->find($this->activeThreadId)
            : null;

        $staff = User::where('tenant_id', $tenantId)->orderBy('name')->get();

        return view('livewire.messenger.messenger-index', [
            'threads' => $threads,
            'activeThread' => $activeThread,
            'sites' => Site::where('tenant_id', $tenantId)->orderBy('name')->get(),
            'staff' => $staff,
            'stats' => [
                'threads' => $threads->count(),
                'messages' => Message::whereIn('message_thread_id', $threads->pluck('id'))->count(),
                'sites' => $threads->pluck('site_id')->filter()->unique()->count(),
                'participants' => $activeThread?->participants->count() ?? 0,
            ],
        ])->layout('layouts.app');
    }
}
