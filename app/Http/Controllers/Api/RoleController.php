<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyRolePermission;
use App\Models\RoleCompany;
use App\Models\User;
use App\Support\SystemRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * API Role Management (mobile RolesScreen) — padanan RoleWebController.
 * Role sistem (admin/member/client) selalu tampil; role custom hanya milik company sendiri.
 */
class RoleController extends Controller
{
    /** GET /api/roles — daftar role yang terlihat oleh tenant (array, tanpa paginate). */
    public function index(Request $request)
    {
        $cid = $this->tenantId();

        $roles = $this->visibleRolesQuery($cid)
            ->when($request->search, fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->orderBy('name')
            ->get();

        $counts = $this->userCounts($roles->pluck('id')->all(), $cid);
        $roles->each(fn(Role $r) => $r->users_count = $counts[$r->id] ?? 0);

        return response()->json($roles->map(fn(Role $r) => $this->transform($r))->values());
    }

    /** GET /api/roles/{role} */
    public function show(Role $role)
    {
        $cid = $this->authorizeRoleOwnership($role);
        $role->users_count = $this->userCounts([$role->id], $cid)[$role->id] ?? 0;

        return response()->json($this->transform($role));
    }

    /** POST /api/roles { name } — permission awal disalin dari role member (versi company bila dikustomisasi). */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:roles,name|alpha_dash',
        ]);

        $cid  = $this->tenantId();
        $role = Role::create(['name' => strtolower($request->name), 'guard_name' => 'web']);

        if ($cid !== null) {
            RoleCompany::create(['role_id' => $role->id, 'company_id' => $cid]);
        }

        $memberRole = Role::where('name', SystemRoles::MEMBER)->first();
        if ($memberRole) {
            $memberPermissions = $memberRole->permissions;
            $role->syncPermissions($memberPermissions);

            if ($cid !== null) {
                $customized = CompanyRolePermission::where('company_id', $cid)
                    ->where('role_id', $memberRole->id)
                    ->pluck('permission_id');

                $permIds = $customized->isNotEmpty() ? $customized : $memberPermissions->pluck('id');
                foreach ($permIds as $permId) {
                    CompanyRolePermission::create([
                        'company_id'    => $cid,
                        'role_id'       => $role->id,
                        'permission_id' => $permId,
                    ]);
                }
            }
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }

        $role->users_count = 0;

        return response()->json([
            'message' => 'Role berhasil dibuat.',
            'data'    => $this->transform($role),
        ], 201);
    }

    /** PUT /api/roles/{role} { name } — role sistem tidak bisa diganti nama. */
    public function update(Request $request, Role $role)
    {
        $cid = $this->authorizeRoleOwnership($role);

        if (in_array($role->name, SystemRoles::ALL, true)) {
            return response()->json(['message' => 'Role default sistem tidak bisa diubah namanya.'], 422);
        }

        $request->validate([
            'name' => 'required|string|max:100|unique:roles,name,' . $role->id . '|alpha_dash',
        ]);

        $role->update(['name' => strtolower($request->name)]);
        $role->users_count = $this->userCounts([$role->id], $cid)[$role->id] ?? 0;

        return response()->json([
            'message' => 'Role berhasil diperbarui.',
            'data'    => $this->transform($role),
        ]);
    }

    /** DELETE /api/roles/{role} — ditolak bila role sistem atau masih dipakai user. */
    public function destroy(Role $role)
    {
        $cid = $this->authorizeRoleOwnership($role);

        if (in_array($role->name, SystemRoles::ALL, true)) {
            return response()->json(['message' => 'Role default sistem tidak bisa dihapus.'], 422);
        }

        $usersCount = $this->userCounts([$role->id], $cid)[$role->id] ?? 0;

        if ($usersCount > 0) {
            return response()->json([
                'message' => "Role tidak bisa dihapus karena masih digunakan oleh {$usersCount} user.",
            ], 422);
        }

        CompanyRolePermission::where('role_id', $role->id)->delete();
        RoleCompany::where('role_id', $role->id)->delete();
        $role->delete();

        return response()->json(['message' => 'Role dihapus.']);
    }

    /**
     * Jumlah user per role (opsional per company). Dihitung langsung dari model_has_roles —
     * relasi Role::users() me-resolve model lewat guard aktif (sanctum) dan gagal di API.
     *
     * @return array<int,int> role_id => jumlah
     */
    private function userCounts(array $roleIds, ?int $cid): array
    {
        if (empty($roleIds)) {
            return [];
        }

        return DB::table('model_has_roles')
            ->join('users', 'users.id', '=', 'model_has_roles.model_id')
            ->where('model_has_roles.model_type', User::class)
            ->whereIn('model_has_roles.role_id', $roleIds)
            ->when($cid !== null, fn($q) => $q->where('users.company_id', $cid))
            ->groupBy('model_has_roles.role_id')
            ->pluck(DB::raw('COUNT(*)'), 'model_has_roles.role_id')
            ->map(fn($c) => (int) $c)
            ->all();
    }

    private function visibleRolesQuery(?int $cid)
    {
        $query = Role::query();

        if ($cid !== null) {
            $ownedByOthers = RoleCompany::where('company_id', '!=', $cid)->pluck('role_id');
            $query->whereNotIn('id', $ownedByOthers);
        }

        return $query;
    }

    /** Abort 403 jika role custom milik company lain. Mengembalikan tenant id. */
    private function authorizeRoleOwnership(Role $role): ?int
    {
        $cid = $this->tenantId();
        if ($cid !== null) {
            $owner = RoleCompany::where('role_id', $role->id)->first();
            abort_if($owner && $owner->company_id !== $cid, 403);
        }

        return $cid;
    }

    private function transform(Role $role): array
    {
        $isSystem = in_array($role->name, SystemRoles::ALL, true);

        return [
            'id'          => $role->id,
            'name'        => $role->name,
            'label'       => ucfirst($role->name),
            'guard_name'  => $role->guard_name,
            'is_system'   => $isSystem,
            'can_edit'    => ! $isSystem,
            'can_delete'  => ! $isSystem && (int) ($role->users_count ?? 0) === 0,
            'users_count' => (int) ($role->users_count ?? 0),
            'created_at'  => $role->created_at?->toIso8601String(),
            'updated_at'  => $role->updated_at?->toIso8601String(),
        ];
    }
}
