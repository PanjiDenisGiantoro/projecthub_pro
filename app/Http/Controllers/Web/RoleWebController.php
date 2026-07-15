<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RoleCompany;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleWebController extends Controller
{
    private array $protected = ['admin', 'manager', 'developer', 'marketing', 'customer'];

    public function index()
    {
        $cid = $this->tenantId();

        $query = Role::withCount(['users' => function ($q) use ($cid) {
            if ($cid !== null) {
                $q->where('company_id', $cid);
            }
        }])->orderBy('name');

        if ($cid !== null) {
            // Role sistem (tanpa owner) selalu tampil, role custom hanya untuk company sendiri.
            $ownedByOthers = RoleCompany::where('company_id', '!=', $cid)->pluck('role_id');
            $query->whereNotIn('id', $ownedByOthers);
        }

        $roles = $query->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        return view('roles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:roles,name|alpha_dash',
        ]);

        $role = Role::create(['name' => strtolower($request->name), 'guard_name' => 'web']);

        $cid = $this->tenantId();
        if ($cid !== null) {
            RoleCompany::create(['role_id' => $role->id, 'company_id' => $cid]);
        }

        return redirect()->route('roles.index')->with('success', 'Role berhasil dibuat.');
    }

    public function edit(Role $role)
    {
        $this->authorizeRoleOwnership($role);
        return view('roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorizeRoleOwnership($role);

        if (in_array($role->name, $this->protected)) {
            return back()->with('warning', 'Role default sistem tidak bisa diubah namanya.');
        }

        $request->validate([
            'name' => 'required|string|max:100|unique:roles,name,' . $role->id . '|alpha_dash',
        ]);

        $role->update(['name' => strtolower($request->name)]);

        return redirect()->route('roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        $this->authorizeRoleOwnership($role);

        if (in_array($role->name, $this->protected)) {
            return back()->with('danger', 'Role default sistem tidak bisa dihapus.');
        }

        $cid = $this->tenantId();
        $usersCount = $cid !== null ? $role->users()->where('company_id', $cid)->count() : $role->users()->count();

        if ($usersCount > 0) {
            return back()->with('danger', 'Role tidak bisa dihapus karena masih digunakan oleh ' . $usersCount . ' user.');
        }

        RoleCompany::where('role_id', $role->id)->delete();
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role dihapus.');
    }

    /** Abort 403 jika role custom ini milik company lain. */
    private function authorizeRoleOwnership(Role $role): void
    {
        $cid = $this->tenantId();
        if ($cid === null) {
            return;
        }

        $owner = RoleCompany::where('role_id', $role->id)->first();
        if ($owner && $owner->company_id !== $cid) {
            abort(403);
        }
    }
}