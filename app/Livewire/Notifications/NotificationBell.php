<?php

namespace App\Livewire\Notifications;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    public function markRead(string $id): void
    {
        $notification = Auth::user()?->notifications()->whereKey($id)->first();

        $notification?->markAsRead();
    }

    public function markAllRead(): void
    {
        Auth::user()?->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.notifications.notification-bell', [
            'notifications' => $user?->notifications()->latest()->limit(12)->get() ?? collect(),
            'unreadCount' => $user?->unreadNotifications()->count() ?? 0,
        ]);
    }
}
