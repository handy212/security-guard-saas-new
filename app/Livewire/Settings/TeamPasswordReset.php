<?php

namespace App\Livewire\Settings;

use App\Models\User;
use App\Services\AuditLogService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class TeamPasswordReset extends Component
{
    public ?int $selectedUserId = null;

    public string $newPassword = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
    }

    public function selectUser(int $userId): void
    {
        $tenantId = TenantContext::current()?->id ?? auth()->user()->tenant_id;

        abort_unless(
            $this->staffQuery($tenantId)->whereKey($userId)->exists(),
            404
        );

        $this->selectedUserId = $userId;
        $this->newPassword = '';
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->selectedUserId = null;
        $this->newPassword = '';
        $this->resetValidation();
    }

    public function resetPassword(AuditLogService $audit): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);

        $tenantId = TenantContext::current()?->id ?? auth()->user()->tenant_id;

        $data = $this->validate([
            'selectedUserId' => ['required', 'integer'],
            'newPassword' => ['required', 'string', Password::min(12)],
        ]);

        $user = $this->staffQuery($tenantId)->findOrFail($data['selectedUserId']);
        $user->update(['password' => Hash::make($data['newPassword'])]);
        $user->tokens()->delete();

        $audit->record('settings.user.password_reset', $user, ['email' => $user->email]);

        $this->selectedUserId = null;
        $this->newPassword = '';
        session()->flash('status', "Password reset for {$user->name}.");
    }

    public function render()
    {
        $tenantId = TenantContext::current()?->id ?? auth()->user()->tenant_id;

        $users = $this->staffQuery($tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status']);

        return view('livewire.settings.team-password-reset', [
            'users' => $users,
        ])->layout('layouts.app');
    }

    private function staffQuery(int $tenantId)
    {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('client_account_id')
            ->whereDoesntHave('guardProfile');
    }
}
