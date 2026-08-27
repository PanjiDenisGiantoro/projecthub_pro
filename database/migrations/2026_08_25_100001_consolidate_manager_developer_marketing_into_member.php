<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Merges 'developer' and 'marketing' roles onto 'manager', then renames the
 * surviving row to 'member'. User assignments and permission grants (global
 * default + per-tenant override) are copied before the two donor roles are
 * deleted, so the FK cascades on delete just clean up already-copied rows.
 *
 * down() is a structural revert only (recreates manager/developer/marketing
 * role rows and points 'member' users back to 'manager') — it cannot recover
 * which of the 3 original roles a given user held, since that information is
 * destroyed by the merge itself. A DB backup taken before up() is the real
 * rollback path.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $conflict = DB::table('roles')->where('name', 'member')->where('guard_name', 'web')->exists();
            if ($conflict) {
                throw new \RuntimeException(
                    "A role named 'member' already exists (likely a tenant custom role) — resolve the name collision before running this migration."
                );
            }

            $manager = DB::table('roles')->where('name', 'manager')->where('guard_name', 'web')->first();
            if (!$manager) {
                return; // nothing to consolidate (e.g. already-migrated environment)
            }

            $developer = DB::table('roles')->where('name', 'developer')->where('guard_name', 'web')->first();
            $marketing = DB::table('roles')->where('name', 'marketing')->where('guard_name', 'web')->first();

            foreach ([$developer, $marketing] as $oldRole) {
                if (!$oldRole) {
                    continue;
                }

                // 1. Reassign users holding this role onto 'manager' (soon 'member').
                DB::table('model_has_roles')
                    ->where('role_id', $oldRole->id)
                    ->where('model_type', 'App\\Models\\User')
                    ->get(['model_id', 'model_type'])
                    ->each(fn ($row) => DB::table('model_has_roles')->insertOrIgnore([
                        'role_id' => $manager->id,
                        'model_id' => $row->model_id,
                        'model_type' => $row->model_type,
                    ]));

                // 2. Union global default permissions (no-op in practice: manager's
                //    permission set is already a superset of developer's/marketing's).
                DB::table('role_has_permissions')->where('role_id', $oldRole->id)
                    ->get(['permission_id'])
                    ->each(fn ($p) => DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $p->permission_id,
                        'role_id' => $manager->id,
                    ]));

                // 3. Union tenant-custom permission overrides.
                DB::table('company_role_permissions')->where('role_id', $oldRole->id)
                    ->get(['company_id', 'permission_id'])
                    ->each(function ($row) use ($manager) {
                        $exists = DB::table('company_role_permissions')
                            ->where('company_id', $row->company_id)
                            ->where('role_id', $manager->id)
                            ->where('permission_id', $row->permission_id)
                            ->exists();
                        if (!$exists) {
                            DB::table('company_role_permissions')->insert([
                                'company_id' => $row->company_id,
                                'role_id' => $manager->id,
                                'permission_id' => $row->permission_id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    });
            }

            // 4. Rename the surviving role row.
            DB::table('roles')->where('id', $manager->id)->update(['name' => 'member', 'updated_at' => now()]);

            // 5. Delete the merged-away role rows. Cascades clean up their
            //    model_has_roles / role_has_permissions / company_role_permissions
            //    / role_companies rows automatically (already copied above).
            foreach ([$developer, $marketing] as $oldRole) {
                if ($oldRole) {
                    DB::table('roles')->where('id', $oldRole->id)->delete();
                }
            }
        });

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::transaction(function () {
            $member = DB::table('roles')->where('name', 'member')->where('guard_name', 'web')->first();
            if (!$member) {
                return;
            }

            DB::table('roles')->where('id', $member->id)->update(['name' => 'manager', 'updated_at' => now()]);

            foreach (['developer', 'marketing'] as $name) {
                DB::table('roles')->insertOrIgnore([
                    'name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
