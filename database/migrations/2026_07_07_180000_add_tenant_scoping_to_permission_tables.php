<?php

use App\Models\User;
use App\Services\TenantRoleProvisioner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PLATFORM_TENANT_ID = 0;

    public function up(): void
    {
        if (! config('permission.teams')) {
            return;
        }

        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teamKey = $columnNames['team_foreign_key'] ?? 'tenant_id';
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $modelKey = $columnNames['model_morph_key'] ?? 'model_id';
        $platformId = self::PLATFORM_TENANT_ID;

        if (! Schema::hasColumn($tableNames['roles'], $teamKey)) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamKey) {
                $table->unsignedBigInteger($teamKey)->default(0)->after('id');
                $table->index($teamKey, 'roles_tenant_id_index');
            });
        }

        DB::table($tableNames['roles'])->whereNull($teamKey)->update([$teamKey => $platformId]);

        if ($this->hasUniqueIndex($tableNames['roles'], 'roles_name_guard_name_unique')
            || $this->hasUniqueIndex($tableNames['roles'], ['name', 'guard_name'])) {
            try {
                Schema::table($tableNames['roles'], function (Blueprint $table) {
                    $table->dropUnique(['name', 'guard_name']);
                });
            } catch (Throwable) {
                Schema::table($tableNames['roles'], function (Blueprint $table) {
                    $table->dropUnique('roles_name_guard_name_unique');
                });
            }
        }

        if (! $this->hasUniqueIndex($tableNames['roles'], 'roles_tenant_name_guard_unique')) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamKey) {
                $table->unique([$teamKey, 'name', 'guard_name'], 'roles_tenant_name_guard_unique');
            });
        }

        $pivotTable = $tableNames['model_has_roles'];

        if (! Schema::hasColumn($pivotTable, $teamKey)) {
            Schema::table($pivotTable, function (Blueprint $table) use ($teamKey, $pivotRole) {
                $table->unsignedBigInteger($teamKey)->default(0)->after($pivotRole);
                $table->index($teamKey, 'model_has_roles_tenant_id_index');
            });
        }

        $tenantIdsByUser = DB::table('users')->pluck('tenant_id', 'id');

        foreach (DB::table($pivotTable)->where('model_type', User::class)->get() as $row) {
            $tenantId = $tenantIdsByUser[$row->{$modelKey}] ?? $platformId;
            $tenantId = $tenantId ?? $platformId;

            DB::table($pivotTable)
                ->where($pivotRole, $row->{$pivotRole})
                ->where($modelKey, $row->{$modelKey})
                ->where('model_type', $row->model_type)
                ->update([$teamKey => $tenantId]);
        }

        DB::table($pivotTable)->whereNull($teamKey)->update([$teamKey => $platformId]);

        if (! $this->hasPrimaryKey($pivotTable, 'model_has_roles_tenant_role_model_primary')) {
            Schema::table($pivotTable, function (Blueprint $table) use ($tableNames, $teamKey, $pivotRole, $modelKey, $pivotTable) {
                if (DB::getDriverName() !== 'sqlite' && $this->hasForeignKey($pivotTable, $pivotRole)) {
                    $table->dropForeign([$pivotRole]);
                }

                if ($this->hasPrimaryKey($pivotTable)) {
                    $table->dropPrimary();
                }

                $table->primary(
                    [$teamKey, $pivotRole, $modelKey, 'model_type'],
                    'model_has_roles_tenant_role_model_primary'
                );

                if (DB::getDriverName() !== 'sqlite') {
                    $table->foreign($pivotRole)
                        ->references('id')->on($tableNames['roles'])->onDelete('cascade');
                }
            });
        }

        $permissionsPivot = $tableNames['model_has_permissions'];

        if (! Schema::hasColumn($permissionsPivot, $teamKey)) {
            Schema::table($permissionsPivot, function (Blueprint $table) use ($teamKey, $pivotPermission) {
                $table->unsignedBigInteger($teamKey)->default(0)->after($pivotPermission);
                $table->index($teamKey, 'model_has_permissions_tenant_id_index');
            });
        }

        if (! $this->hasPrimaryKey($permissionsPivot, 'model_has_permissions_tenant_perm_model_primary')) {
            Schema::table($permissionsPivot, function (Blueprint $table) use ($tableNames, $teamKey, $pivotPermission, $modelKey, $permissionsPivot) {
                if (DB::getDriverName() !== 'sqlite' && $this->hasForeignKey($permissionsPivot, $pivotPermission)) {
                    $table->dropForeign([$pivotPermission]);
                }

                if ($this->hasPrimaryKey($permissionsPivot)) {
                    $table->dropPrimary();
                }

                $table->primary(
                    [$teamKey, $pivotPermission, $modelKey, 'model_type'],
                    'model_has_permissions_tenant_perm_model_primary'
                );

                if (DB::getDriverName() !== 'sqlite') {
                    $table->foreign($pivotPermission)
                        ->references('id')->on($tableNames['permissions'])->onDelete('cascade');
                }
            });
        }

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));

        $provisioner = app(TenantRoleProvisioner::class);
        $provisioner->ensurePlatformRoles();
        $provisioner->migrateLegacyAssignments();
        $provisioner->provisionAllTenants();
    }

    public function down(): void
    {
    }

    private function hasPrimaryKey(string $table, ?string $name = null): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            if ($name !== null) {
                return collect($indexes)->contains(fn ($index) => ($index->name ?? '') === $name);
            }

            return collect($indexes)->contains(fn ($index) => ($index->origin ?? '') === 'pk');
        }

        $database = DB::getDatabaseName();

        if ($name !== null) {
            $result = DB::select(
                'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
                [$database, $table, $name]
            );

            return (int) ($result[0]->aggregate ?? 0) > 0;
        }

        $result = DB::select(
            'SELECT COUNT(*) AS aggregate FROM information_schema.table_constraints WHERE table_schema = ? AND table_name = ? AND constraint_type = ?',
            [$database, $table, 'PRIMARY KEY']
        );

        return (int) ($result[0]->aggregate ?? 0) > 0;
    }

    private function hasForeignKey(string $table, string $column): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return false;
        }

        $database = DB::getDatabaseName();
        $result = DB::select(
            'SELECT COUNT(*) AS aggregate FROM information_schema.key_column_usage WHERE table_schema = ? AND table_name = ? AND column_name = ? AND referenced_table_name IS NOT NULL',
            [$database, $table, $column]
        );

        return (int) ($result[0]->aggregate ?? 0) > 0;
    }

    private function hasUniqueIndex(string $table, array|string $index): bool
    {
        $indexName = is_array($index) ? implode('_', $index).'_unique' : $index;

        if (DB::getDriverName() === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn ($row) => ($row->name ?? '') === $indexName);
        }

        $database = DB::getDatabaseName();
        $result = DB::select(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return (int) ($result[0]->aggregate ?? 0) > 0;
    }
};
