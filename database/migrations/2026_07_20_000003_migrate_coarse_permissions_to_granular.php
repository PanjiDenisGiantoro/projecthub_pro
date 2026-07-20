<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Permission lama ("manage X") dipecah jadi granular (create/view/update/delete X).
     * Supaya role/company yang sudah punya permission lama tidak kehilangan akses,
     * setiap role/company yang punya permission lama otomatis diberi permission baru
     * yang setara. Permission lama TIDAK dihapus (dibiarkan menggantung, tidak dipakai
     * lagi di kode) supaya migration ini murni aditif & aman di-rollback.
     */
    private array $mapping = [
        'manage campaigns'         => ['create campaign', 'update campaign', 'delete campaign'],
        'manage invoices'          => ['create invoice', 'update invoice'],
        'manage approval policies' => ['create approval policy', 'update approval policy', 'delete approval policy'],
        'manage users'             => ['create user', 'update user', 'delete user'],
        'manage master data'      => ['create master data', 'update master data', 'delete master data'],
        'manage absensi'           => ['view absensi', 'update absensi'],
        'manage leave'             => ['view leave'],
        'manage overtime'          => ['view overtime'],
        'manage reimbursement'     => ['view reimbursement'],
        'manage payroll'           => ['view payroll', 'create payroll', 'update payroll', 'delete payroll'],
        'generate payroll'         => ['create payroll'],
        'view payroll report'      => ['view payroll'],
        'manage hris master'       => ['view hris master', 'create hris master', 'update hris master', 'delete hris master'],
    ];

    public function up(): void
    {
        foreach ($this->mapping as $oldName => $newNames) {
            $old = Permission::where('name', $oldName)->where('guard_name', 'web')->first();
            if (! $old) {
                continue;
            }

            $newIds = collect($newNames)->map(
                fn($name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'])->id
            );

            // role_has_permissions (default global per role)
            $roleIds = DB::table('role_has_permissions')->where('permission_id', $old->id)->pluck('role_id');
            foreach ($roleIds as $roleId) {
                foreach ($newIds as $newId) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $newId,
                        'role_id'       => $roleId,
                    ]);
                }
            }

            // company_role_permissions (kustomisasi per company)
            $rows = DB::table('company_role_permissions')->where('permission_id', $old->id)->get(['company_id', 'role_id']);
            foreach ($rows as $row) {
                foreach ($newIds as $newId) {
                    $exists = DB::table('company_role_permissions')
                        ->where('company_id', $row->company_id)
                        ->where('role_id', $row->role_id)
                        ->where('permission_id', $newId)
                        ->exists();

                    if (! $exists) {
                        DB::table('company_role_permissions')->insert([
                            'company_id'    => $row->company_id,
                            'role_id'       => $row->role_id,
                            'permission_id' => $newId,
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ]);
                    }
                }
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Aditif-only, sengaja tidak di-revert (menghapus grant baru berisiko
        // menghapus izin yang sudah dipakai/diandalkan setelah migration jalan).
    }
};
