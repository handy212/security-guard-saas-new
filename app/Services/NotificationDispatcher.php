<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use App\Models\User;
use App\Notifications\GenericGuardOpsNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class NotificationDispatcher
{
    public function sendToTenantAdmins(int $tenantId, string $templateCode, array $replacements, ?Notification $fallback = null): void
    {
        $users = User::role(['company-admin', 'operations-manager', 'supervisor'])
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->get();

        $this->sendToUsers($users, $tenantId, $templateCode, $replacements, $fallback);
    }

    public function sendToUser(User $user, string $templateCode, array $replacements, ?Notification $fallback = null): void
    {
        $this->sendToUsers(collect([$user]), $user->tenant_id, $templateCode, $replacements, $fallback);
    }

    private function sendToUsers(Collection $users, int $tenantId, string $templateCode, array $replacements, ?Notification $fallback): void
    {
        $template = NotificationTemplate::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $templateCode)
            ->where('is_active', true)
            ->first();

        foreach ($users as $user) {
            if ($template) {
                $user->notify(new GenericGuardOpsNotification(
                    $this->replace($template->subject, $replacements),
                    $this->replace($template->body, $replacements),
                ));
            } elseif ($fallback) {
                $user->notify($fallback);
            }
        }
    }

    private function replace(?string $text, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $text = str_replace('{{'.$key.'}}', (string) $value, (string) $text);
        }

        return (string) $text;
    }
}
