<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('billing.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('billing.manage');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->can('billing.manage') && $user->tenant_id === $invoice->tenant_id;
    }
}
