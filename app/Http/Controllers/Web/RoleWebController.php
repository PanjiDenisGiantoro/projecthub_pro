<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleWebController extends Controller
{
    private array $protected = ['admin', 'manager', 'developer', 'marketing', 'customer'];

    public function index()
    {
        $roles = Role::withCount('users')->orderBy('name')->get();
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

        Role::create(['name' => strtolower($request->name), 'guard_name' => 'web']);

        return redirect()->route('roles.index')->with('success', 'Role berhasil dibuat.');
    }

    public function edit(Role $role)
    {
        return view('roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
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
        if (in_array($role->name, $this->protected)) {
            return back()->with('danger', 'Role default sistem tidak bisa dihapus.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('danger', 'Role tidak bisa dihapus karena masih digunakan oleh ' . $role->users()->count() . ' user.');
        }

        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role dihapus.');
    }
}