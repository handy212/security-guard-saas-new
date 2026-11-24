<?php

namespace App\Livewire\Settings;

use App\Models\User;
use App\Services\AuditLogService;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class TeamPasswordReset extends Component
{
    public ?int $selectedUserId = null;

    public string $newPassword = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.manage') && ! TenantContext::isPlatformConsole(), 403);
    }

    public function selectUser(int $userId): void
    {
        abort_unless(auth()->user()->can('settings.manage') && ! TenantContext::isPlatformConsole(), 403);

        $tenantId = TenantContext::id();

        abort_unless(
            $this->teamUsers($tenantId)->whereKey($userId)->exists(),
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
        abort_unless(auth()->user()->can('settings.manage') && ! TenantContext::isPlatformConsole(), 403);

        $tenantId = TenantContext::id();

        $data = $this->validate([
            'selectedUserId' => ['required', 'integer'],
            'newPassword' => ['required', 'string', Password::min(12)],
        ]);

        $user = $this->teamUsers($tenantId)->findOrFail($data['selectedUserId']);
        DB::transaction(function () use ($user, $data): void {
            $user->update(['password' => Hash::make($data['newPassword'])]);
            $user->tokens()->delete();
        });

        $audit->record('settings.user.password_reset', $user, ['email' => $user->email]);

        $this->selectedUserId = null;
        $this->newPassword = '';
        session()->flash('status', "Password reset for {$user->name}.");
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        $users = $this->teamUsers($tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status']);

        return view('livewire.settings.team-password-reset', [
            'users' => $users,
        ])->layout('layouts.app');
    }

    private function teamUsers(int $tenantId): Builder
    {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('client_account_id');
    }
}
