<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionWebController extends Controller
{
    public function index()
    {
        $groups = config('permissions');
        $roles  = Role::whereNotIn('name', ['admin'])->orderBy('name')->get();

        $rolePermissions = $roles->mapWithKeys(fn($role) => [
            $role->name => $role->permissions->pluck('name')->toArray(),
        ]);

        $stats = [
            'total_permissions' => Permission::count(),
            'total_roles'       => Role::count(),
        ];

        return view('permissions.index', compact('groups', 'roles', 'rolePermissions', 'stats'));
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

        $allPerms    = collect(config('permissions'))->flatMap(fn($items) => array_keys($items))->toArray();
        $newPerms    = array_intersect($request->input('permissions', []), $allPerms);

        $role->syncPermissions($newPerms);

        // Clear Spatie permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return back()->with('success', "Permission untuk role <strong>" . ucfirst($roleName) . "</strong> berhasil disimpan.");
    }

    public function resetRole(string $roleName)
    {
        if ($roleName === 'admin') {
            return back()->with('error', 'Tidak dapat mereset permission admin.');
        }

        // Re-run seeder for this role
        app(\Database\Seeders\PermissionSeeder::class)->run();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return back()->with('success', "Permission role <strong>" . ucfirst($roleName) . "</strong> berhasil direset ke default.");
    }
}
