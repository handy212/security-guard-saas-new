<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Support\TenantContext;

class MessengerService
{
    public function createThread(int $siteId, string $subject, array $participantUserIds): MessageThread
    {
        $thread = MessageThread::create([
            'tenant_id' => TenantContext::id(),
            'site_id' => $siteId,
            'subject' => $subject,
            'type' => 'site',
        ]);

        foreach ($participantUserIds as $userId) {
            MessageThreadParticipant::create([
                'message_thread_id' => $thread->id,
                'user_id' => $userId,
            ]);
        }

        return $thread;
    }

    public function sendMessage(MessageThread $thread, int $userId, string $body, ?string $attachmentPath = null): Message
    {
        $message = Message::create([
            'message_thread_id' => $thread->id,
            'user_id' => $userId,
            'body' => $body,
            'attachment_path' => $attachmentPath,
        ]);

        event(new MessageSent($message));

        return $message;
    }
}
