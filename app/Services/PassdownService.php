<?php

namespace App\Services;

use App\Models\PassdownLog;
use App\Support\TenantContext;

class PassdownService
{
    public function create(array $data): PassdownLog
    {
        return PassdownLog::create($data + [
            'tenant_id' => TenantContext::id(),
        ]);
    }

    public function update(PassdownLog $passdown, array $data): PassdownLog
    {
        $passdown->update(collect($data)->only([
            'site_id', 'site_post_id', 'guard_id', 'shift_assignment_id', 'content',
        ])->filter(fn ($v) => $v !== null)->all());

        return $passdown->fresh();
    }

    public function delete(PassdownLog $passdown): void
    {
        $passdown->delete();
    }
}
