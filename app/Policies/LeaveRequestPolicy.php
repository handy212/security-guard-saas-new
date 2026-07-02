<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('schedules.manage') || $user->can('guards.manage');
    }

    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->can('schedules.manage') && $user->tenant_id === $leaveRequest->tenant_id;
    }
}
