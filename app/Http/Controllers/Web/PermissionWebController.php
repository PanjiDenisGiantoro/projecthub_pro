<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CompanyRolePermission;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionWebController extends Controller
{
    public function index()
    {
        $cid    = $this->tenantId();
        $groups = config('permissions');
        $roles  = Role::whereNotIn('name', ['admin'])->orderBy('name')->get();

        $customizedRoleNames = [];

        if ($cid) {
            $customizedRoleIds = CompanyRolePermission::where('company_id', $cid)
                ->whereIn('role_id', $roles->pluck('id'))
                ->pluck('role_id')
                ->unique();

            $customizedRoleNames = $roles->whereIn('id', $customizedRoleIds)->pluck('name')->all();

            $rolePermissions = $roles->mapWithKeys(function ($role) use ($cid, $customizedRoleIds) {
                if ($customizedRoleIds->contains($role->id)) {
                    $names = CompanyRolePermission::where('company_id', $cid)->where('role_id', $role->id)
                        ->with('permission')->get()->pluck('permission.name')->filter()->toArray();
                } else {
                    $names = $role->permissions->pluck('name')->toArray();
                }
                return [$role->name => $names];
            });
        } else {
            // Super admin mengelola template default global
            $rolePermissions = $roles->mapWithKeys(fn($role) => [
                $role->name => $role->permissions->pluck('name')->toArray(),
            ]);
        }

        $stats = [
            'total_permissions' => Permission::count(),
            'total_roles'       => Role::count(),
        ];

        return view('permissions.index', compact('groups', 'roles', 'rolePermissions', 'stats', 'customizedRoleNames', 'cid'));
    }

    public function update(Request $request, string $roleName)
    {
        if ($roleName === 'admin') {
            return back()->with('error', 'Permission admin tidak dapat diubah — admin selalu memiliki akses penuh.');
        }

        $role = Role::findByName($roleName, 'web');

        if (!$role) {
            return back()->with('error', "Role '{$roleName}' tidak ditemukan.");
        }

        $allPerms     = collect(config('permissions'))->flatMap(fn($items) => array_keys($items))->toArray();
        $newPermNames = array_intersect($request->input('permissions', []), $allPerms);

        $cid = $this->tenantId();

        if ($cid === null) {
            $role->syncPermissions($newPermNames);
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return back()->with('success', "Permission default untuk role <strong>" . ucfirst($roleName) . "</strong> berhasil disimpan.");
        }

        $permIds = Permission::whereIn('name', $newPermNames)->pluck('id');

        CompanyRolePermission::where('company_id', $cid)->where('role_id', $role->id)->delete();
        foreach ($permIds as $permId) {
            CompanyRolePermission::create(['company_id' => $cid, 'role_id' => $role->id, 'permission_id' => $permId]);
        }

        return back()->with('success', "Permission untuk role <strong>" . ucfirst($roleName) . "</strong> berhasil disimpan untuk perusahaan Anda.");
    }

    public function resetRole(string $roleName)
    {
        if ($roleName === 'admin') {
            return back()->with('error', 'Tidak dapat mereset permission admin.');
        }

        $cid = $this->tenantId();

        if ($cid === null) {
            // Re-run seeder untuk template default global
            app(\Database\Seeders\PermissionSeeder::class)->run();
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return back()->with('success', "Permission role <strong>" . ucfirst($roleName) . "</strong> berhasil direset ke default.");
        }

        $role = Role::findByName($roleName, 'web');
        if ($role) {
            CompanyRolePermission::where('company_id', $cid)->where('role_id', $role->id)->delete();
        }

        return back()->with('success', "Permission role <strong>" . ucfirst($roleName) . "</strong> untuk perusahaan Anda dikembalikan ke default.");
    }
}