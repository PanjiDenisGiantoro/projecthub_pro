<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyRolePermission;
use App\Models\RoleCompany;
use App\Support\SystemRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * API matriks Permission (mobile PermissionsScreen) — padanan PermissionWebController.
 * Tenant: simpan ke company_role_permissions (override per company).
 * Super admin (tenant null): mengubah template default global (role_has_permissions).
 */
class PermissionController extends Controller
{
    /** GET /api/permissions — grup permission, daftar role, dan matriks role → permission. */
    public function index(Request $request)
    {
        $cid   = $this->tenantId();
        $roles = $this->rolesQuery($cid)->with('permissions:id,name')->get();

        $userPkgs = $request->user()->accessiblePackages();
        $showTm   = $cid === null || empty($userPkgs) || in_array('task_management', $userPkgs);
        $showHris = $cid === null || in_array('hris', $userPkgs);

        $customizedRoleIds = $cid !== null
            ? CompanyRolePermission::where('company_id', $cid)->whereIn('role_id', $roles->pluck('id'))->distinct()->pluck('role_id')
            : collect();

        $companyPerms = $customizedRoleIds->isNotEmpty()
            ? CompanyRolePermission::where('company_id', $cid)
                ->whereIn('role_id', $customizedRoleIds)
                ->join('permissions', 'permissions.id', '=', 'company_role_permissions.permission_id')
                ->get(['company_role_permissions.role_id', 'permissions.name'])
                ->groupBy('role_id')
            : collect();

        $groups = collect(config('permissions'))
            ->map(function ($items, $name) use ($showTm, $showHris) {
                $module = str_starts_with($name, 'HRIS') ? 'hris' : 'task_management';
                $visible = $module === 'hris' ? $showHris : $showTm;
                if (! $visible) {
                    return null;
                }

                return [
                    'name'        => $name,
                    'module'      => $module,
                    'permissions' => collect($items)->map(fn($label, $key) => ['name' => $key, 'label' => $label])->values(),
                ];
            })
            ->filter()
            ->values();

        $rolesOut = $roles->map(function (Role $role) use ($customizedRoleIds, $companyPerms) {
            $customized = $customizedRoleIds->contains($role->id);
            $perms = $customized
                ? $companyPerms->get($role->id, collect())->pluck('name')
                : $role->permissions->pluck('name');

            return [
                'id'            => $role->id,
                'name'          => $role->name,
                'label'         => ucfirst($role->name),
                'is_system'     => in_array($role->name, SystemRoles::ALL, true),
                'is_customized' => $customized,
                'permissions'   => $perms->values(),
            ];
        })->values();

        return response()->json([
            'scope'   => $cid === null ? 'global' : 'company',
            'modules' => ['task_management' => $showTm, 'hris' => $showHris],
            'groups'  => $groups,
            'roles'   => $rolesOut,
            'stats'   => [
                'total_permissions' => Permission::count(),
                'total_roles'       => $roles->count() + 1, // + admin (selalu akses penuh, tidak tampil di matriks)
            ],
            'note' => 'Role admin selalu memiliki akses penuh dan tidak bisa diubah.',
        ]);
    }

    /** PUT /api/permissions/{role} { permissions: ["access projects", ...] } — set lengkap (replace). */
    public function update(Request $request, string $roleName)
    {
        if ($roleName === SystemRoles::ADMIN) {
            return response()->json(['message' => 'Permission admin tidak dapat diubah — admin selalu memiliki akses penuh.'], 422);
        }

        $request->validate([
            'permissions'   => 'present|array',
            'permissions.*' => 'string',
        ]);

        $role = $this->findRole($roleName);
        $cid  = $this->authorizeRole($role);

        $allPerms     = collect(config('permissions'))->flatMap(fn($items) => array_keys($items))->all();
        $newPermNames = array_values(array_intersect($request->input('permissions', []), $allPerms));

        if ($cid === null) {
            $role->syncPermissions($newPermNames);
        } else {
            $permIds = Permission::whereIn('name', $newPermNames)->pluck('id');
            DB::transaction(function () use ($cid, $role, $permIds) {
                CompanyRolePermission::where('company_id', $cid)->where('role_id', $role->id)->delete();
                foreach ($permIds as $permId) {
                    CompanyRolePermission::create(['company_id' => $cid, 'role_id' => $role->id, 'permission_id' => $permId]);
                }
            });
        }
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'message' => $cid === null
                ? 'Permission default untuk role ' . ucfirst($role->name) . ' berhasil disimpan.'
                : 'Permission untuk role ' . ucfirst($role->name) . ' berhasil disimpan untuk perusahaan Anda.',
            'data' => [
                'role'          => $role->name,
                'is_customized' => $cid !== null,
                'permissions'   => $newPermNames,
            ],
        ]);
    }

    /** POST /api/permissions/{role}/reset — kembalikan ke default (hapus override company). */
    public function reset(string $roleName)
    {
        if ($roleName === SystemRoles::ADMIN) {
            return response()->json(['message' => 'Tidak dapat mereset permission admin.'], 422);
        }

        $role = $this->findRole($roleName);
        $cid  = $this->authorizeRole($role);

        if ($cid === null) {
            app(\Database\Seeders\PermissionSeeder::class)->run();
        } else {
            CompanyRolePermission::where('company_id', $cid)->where('role_id', $role->id)->delete();
        }
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'message' => 'Permission role ' . ucfirst($role->name) . ' dikembalikan ke default.',
            'data'    => [
                'role'          => $role->name,
                'is_customized' => false,
                'permissions'   => $role->fresh()->permissions->pluck('name')->values(),
            ],
        ]);
    }

    private function rolesQuery(?int $cid)
    {
        $query = Role::where('name', '!=', SystemRoles::ADMIN)->orderBy('name');

        if ($cid !== null) {
            $query->whereNotIn('id', RoleCompany::where('company_id', '!=', $cid)->pluck('role_id'));
        }

        return $query;
    }

    private function findRole(string $roleName): Role
    {
        $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
        abort_unless($role, 404, "Role '{$roleName}' tidak ditemukan.");

        return $role;
    }

    private function authorizeRole(Role $role): ?int
    {
        $cid = $this->tenantId();
        if ($cid !== null) {
            $owner = RoleCompany::where('role_id', $role->id)->first();
            abort_if($owner && $owner->company_id !== $cid, 403);
        }

        return $cid;
    }
}
