<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\OrganizationUnit;
use App\Models\Package;
use App\Models\StructuralLevel;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserWebController extends Controller
{
    public function index(Request $request)
    {
        $authUser  = auth()->user();
        $isAdmin   = $authUser->can('manage users');

        $query = User::with(['roles', 'structuralLevel', 'organizationUnit'])
            ->where('is_super_admin', false)
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'customer'))
            ->when($request->role, fn($q) => $q->role($request->role))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"));

        // Selalu scope ke company sendiri — super admin tidak masuk sini (middleware superadmin terpisah)
        if ($authUser->company_id) {
            $query->where('company_id', $authUser->company_id);
        } elseif (! $isAdmin) {
            $query->where('id', $authUser->id);
        }

        $users = $query->paginate(20);
        $roles = $isAdmin ? Role::where('name', '!=', 'customer')->get() : collect();

        return view('users.index', compact('users', 'roles', 'isAdmin'));
    }

    /**
     * Admin Tim — daftar user ber-role admin di company sendiri, termasuk yang
     * juga is_super_admin=true (mis. admin yang punya akses /superadmin).
     * Dipisah dari index() supaya /users (Anggota Tim) tetap fokus ke tim non-admin.
     */
    public function adminTeam(Request $request)
    {
        $authUser = auth()->user();

        $users = User::with(['roles', 'structuralLevel', 'organizationUnit'])
            ->whereHas('roles', fn($q) => $q->where('name', 'admin'))
            ->where('company_id', $authUser->company_id)
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->paginate(20);

        return view('users.admin-team', compact('users'));
    }

    public function create()
    {
        $roles             = Role::all();
        $structuralLevels  = StructuralLevel::active()->where('company_id', auth()->user()->company_id)->get();
        $organizationUnits = OrganizationUnit::orderedTree(auth()->user()->company_id);
        return view('users.create', compact('roles', 'structuralLevels', 'organizationUnits'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users',
            'password'              => 'required|min:8|confirmed',
            'role'                  => 'required|exists:roles,name',
            'structural_level_id'   => 'nullable|exists:structural_levels,id',
            'organization_unit_id'  => 'nullable|exists:organization_units,id',
        ]);

        $user = User::create([
            'name'                 => $request->name,
            'email'                => $request->email,
            'password'             => $request->password,
            'company_id'           => auth()->user()->company_id,
            'is_active'            => $request->boolean('is_active', true),
            'structural_level_id'  => $request->structural_level_id,
            'organization_unit_id' => $request->organization_unit_id,
        ]);
        $user->assignRole($request->role);

        // User baru mengikuti package yang sudah dipakai company-nya,
        // supaya tidak perlu di-assign manual satu-satu oleh super admin.
        $companyPackageIds = Package::whereHas('users', function ($q) {
            $q->where('company_id', auth()->user()->company_id);
        })->pluck('id');
        $user->packages()->sync($companyPackageIds);

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $roles             = Role::all();
        $structuralLevels  = StructuralLevel::active()->where('company_id', auth()->user()->company_id)->get();
        $organizationUnits = OrganizationUnit::orderedTree(auth()->user()->company_id);

        return view('users.edit', compact('user', 'roles', 'structuralLevels', 'organizationUnits'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'                 => 'required|string|max:255',
            'email'                => 'required|email|unique:users,email,' . $user->id,
            'role'                 => 'required|exists:roles,name',
            'structural_level_id'  => 'nullable|exists:structural_levels,id',
            'organization_unit_id' => 'nullable|exists:organization_units,id',
        ]);

        $user->update([
            ...$request->only('name', 'email', 'timezone', 'structural_level_id', 'organization_unit_id'),
            'is_active' => $request->boolean('is_active'),
        ]);
        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')->with('success', 'User diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['Tidak bisa menghapus akun sendiri.']);
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User dihapus.');
    }
}
