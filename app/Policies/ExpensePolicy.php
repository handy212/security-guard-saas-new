<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('billing.manage');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->can('billing.manage') && $user->tenant_id === $expense->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('billing.manage');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->can('billing.manage') && $user->tenant_id === $expense->tenant_id;
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->can('billing.manage') && $user->tenant_id === $expense->tenant_id;
    }
}
