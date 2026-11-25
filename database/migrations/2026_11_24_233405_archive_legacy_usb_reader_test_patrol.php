<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('patrol_routes')
            ->where('name', 'USB reader test')
            ->whereIn('status', ['active', 'inactive'])
            ->whereExists(fn (Builder $query) => $this->hasCheckpoint($query, 'USB-TEST-01'))
            ->whereExists(fn (Builder $query) => $this->hasCheckpoint($query, 'USB-TEST-02'))
            ->update(['status' => 'archived']);
    }

    public function down(): void
    {
        // Keep retired test routes archived when rolling back application code.
    }

    private function hasCheckpoint(Builder $query, string $code): Builder
    {
        return $query->select('id')->from('patrol_checkpoints')
            ->whereColumn('patrol_route_id', 'patrol_routes.id')
            ->whereColumn('tenant_id', 'patrol_routes.tenant_id')
            ->where('code', $code);
    }
};
