<?php

namespace App\Support;

use Illuminate\Validation\Rule;

use Illuminate\Validation\Rules\Exists;

class TenantValidation
{
    public static function exists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where('tenant_id', TenantContext::id());
    }

    public static function existsForTenant(int $tenantId, string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where('tenant_id', $tenantId);
    }
}
