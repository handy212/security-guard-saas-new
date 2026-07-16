<?php

namespace App\Policies;

use App\Models\ReportTemplate;
use App\Models\User;

class ReportTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reports.approve');
    }

    public function view(User $user, ReportTemplate $reportTemplate): bool
    {
        return $user->can('reports.approve') && $user->tenant_id === $reportTemplate->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('reports.approve');
    }

    public function update(User $user, ReportTemplate $reportTemplate): bool
    {
        return $user->can('reports.approve') && $user->tenant_id === $reportTemplate->tenant_id;
    }

    public function delete(User $user, ReportTemplate $reportTemplate): bool
    {
        return $user->can('reports.approve') && $user->tenant_id === $reportTemplate->tenant_id;
    }
}
