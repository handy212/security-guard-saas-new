<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('billing.manage') || $user->can('client_portal.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ((int) $user->tenant_id !== (int) $invoice->tenant_id) {
            return false;
        }

        if ($user->can('billing.manage')) {
            return true;
        }

        return $user->can('client_portal.view')
            && $user->client_account_id
            && (int) $user->client_account_id === (int) $invoice->client_account_id
            && in_array($invoice->status, ['sent', 'partial', 'paid', 'overdue'], true);
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
