<?php

namespace App\Livewire\Notifications;

use App\Services\NotificationDispatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
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
        Auth::user()?->unreadNotifications()->when(NotificationDispatcher::alarmsEnabled(), fn ($query) => $query->whereNull('archived_at'))->update(['read_at' => now()]);
    }

    public function acknowledge(int $id, NotificationDispatcher $dispatcher): void
    {
        abort_unless(NotificationDispatcher::canOperateAlarms(Auth::user()), 404);
        $dispatcher->acknowledgeAlarm(Auth::user(), $id);
    }

    #[On('notification-state-changed')]
    public function refreshNotifications(): void {}

    public function render(): View
    {
        $user = Auth::user();

        $critical = $user && NotificationDispatcher::canOperateAlarms($user)
            ? app(NotificationDispatcher::class)->alarmsFor($user)->where('severity', 'critical')
                ->whereNull('resolved_at')->whereNull('acknowledged_at')
            : null;

        $notifications = $user?->notifications()->when(NotificationDispatcher::alarmsEnabled(), fn ($query) => $query->whereNull('archived_at'))->latest()->limit(12)->get() ?? collect();
        $notice = $notifications->first(fn ($notification) => ! $notification->read_at
            && in_array($notification->data['severity'] ?? 'info', ['warning', 'high'], true));
        $noticeUrl = $notice?->data['action_url'] ?? route('notifications.index', absolute: false);
        if (! str_starts_with($noticeUrl, '/') || str_starts_with($noticeUrl, '//') || str_contains($noticeUrl, '\\')) {
            $noticeUrl = route('notifications.index', absolute: false);
        }

        return view('livewire.notifications.notification-bell', [
            'notifications' => $notifications,
            'routineNotice' => $notice ? [
                'id' => $notice->id,
                'title' => $notice->data['title'] ?? 'Notification',
                'severity' => $notice->data['severity'],
                'url' => $noticeUrl,
            ] : null,
            'canOperateAlarms' => $critical !== null,
            'criticalCount' => $critical?->count() ?? 0,
            'alarms' => $critical?->orderByDesc('priority')->oldest()->orderBy('id')->limit(5)->get() ?? collect(),
            'unreadCount' => $user?->unreadNotifications()->when(NotificationDispatcher::alarmsEnabled(), fn ($query) => $query->whereNull('archived_at'))->count() ?? 0,
        ]);
    }
}
