<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renames the 'customer' Spatie role to 'client'. Pure rename (same role id),
 * so model_has_roles / role_has_permissions / company_role_permissions /
 * role_companies rows all stay correctly attached automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $conflict = DB::table('roles')->where('name', 'client')->where('guard_name', 'web')->exists();
            if ($conflict) {
                throw new \RuntimeException(
                    "A role named 'client' already exists (likely a tenant custom role) — resolve the name collision before running this migration."
                );
            }

            DB::table('roles')->where('name', 'customer')->where('guard_name', 'web')
                ->update(['name' => 'client', 'updated_at' => now()]);
        });

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'client')->where('guard_name', 'web')
            ->update(['name' => 'customer', 'updated_at' => now()]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
