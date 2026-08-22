<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\OrganizationUnit;
use App\Models\Package;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\StructuralLevel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserWebController extends Controller
{
    use HasPerPage;

    public function index(Request $request)
    {
        $authUser  = auth()->user();
        $canCreate = $authUser->can('create user');
        $canUpdate = $authUser->can('update user');
        $canDelete = $authUser->can('delete user');
        $isAdmin   = $canCreate || $canUpdate || $canDelete;

        $baseScope = function ($q) use ($authUser, $isAdmin) {
            $q->whereDoesntHave('roles', fn($r) => $r->where('name', 'customer'));

            if ($authUser->company_id) {
                $q->where('company_id', $authUser->company_id);
            } elseif (! $isAdmin) {
                $q->where('id', $authUser->id);
            }
        };

        $query = User::with(['roles', 'structuralLevel', 'organizationUnit', 'projects:id,name'])
            ->tap($baseScope)
            ->when($request->role, fn($q) => $q->role($request->role))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"));

        $users = $query->paginate($this->perPage($request))->withQueryString();
        $roles = $isAdmin ? Role::whereNotIn('name', ['customer', 'tester'])->get() : collect();

        $totalUsers    = User::tap($baseScope)->count();
        $activeUsers   = User::tap($baseScope)->where('is_active', true)->count();
        $inactiveUsers = User::tap($baseScope)->where('is_active', false)->count();

        return view('users.index', compact(
            'users', 'roles', 'isAdmin', 'canCreate', 'canUpdate', 'canDelete',
            'totalUsers', 'activeUsers', 'inactiveUsers'
        ));
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
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('users.admin-team', compact('users'));
    }

    public function create()
    {
        $roles             = Role::whereNotIn('name', ['tester', 'customer'])->get();
        $structuralLevels  = StructuralLevel::active()->where('company_id', auth()->user()->company_id)->get();
        $organizationUnits = OrganizationUnit::orderedTree(auth()->user()->company_id);
        $projects          = Project::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        return view('users.create', compact('roles', 'structuralLevels', 'organizationUnits', 'projects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users',
            'password'              => 'required|min:8|confirmed',
            'role'                  => 'required|exists:roles,name|not_in:customer',
            'structural_level_id'   => 'nullable|exists:structural_levels,id',
            'organization_unit_id'  => 'nullable|exists:organization_units,id',
            'project_ids'           => 'nullable|array',
            'project_ids.*'         => [Rule::exists('projects', 'id')->where('company_id', auth()->user()->company_id)],
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

        foreach ($request->input('project_ids', []) as $projectId) {
            ProjectMember::firstOrCreate(['project_id' => $projectId, 'user_id' => $user->id]);
        }

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
        $projects          = Project::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $selectedProjectIds = ProjectMember::where('user_id', $user->id)->pluck('project_id')->toArray();

        return view('users.edit', compact('user', 'roles', 'structuralLevels', 'organizationUnits', 'projects', 'selectedProjectIds'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'                 => 'required|string|max:255',
            'email'                => 'required|email|unique:users,email,' . $user->id,
            'role'                 => 'required|exists:roles,name',
            'structural_level_id'  => 'nullable|exists:structural_levels,id',
            'organization_unit_id' => 'nullable|exists:organization_units,id',
            'project_ids'          => 'nullable|array',
            'project_ids.*'        => [Rule::exists('projects', 'id')->where('company_id', auth()->user()->company_id)],
        ]);

        $user->update([
            ...$request->only('name', 'email', 'timezone', 'structural_level_id', 'organization_unit_id'),
            'is_active' => $request->boolean('is_active'),
        ]);
        $user->syncRoles([$request->role]);

        $companyProjectIds = Project::where('company_id', auth()->user()->company_id)->pluck('id');
        $selectedProjectIds = $request->input('project_ids', []);

        ProjectMember::where('user_id', $user->id)
            ->whereIn('project_id', $companyProjectIds)
            ->whereNotIn('project_id', $selectedProjectIds)
            ->delete();

        foreach ($selectedProjectIds as $projectId) {
            ProjectMember::firstOrCreate(['project_id' => $projectId, 'user_id' => $user->id]);
        }

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
