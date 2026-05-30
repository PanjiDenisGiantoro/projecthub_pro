<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\StructuralLevel;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserWebController extends Controller
{
    public function index(Request $request)
    {
        $authUser  = auth()->user();
        $isAdmin   = $authUser->hasRole('admin');

        $query = User::with(['roles', 'structuralLevel', 'department'])
            ->where('is_super_admin', false)
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
        $roles = $isAdmin ? Role::all() : collect();

        return view('users.index', compact('users', 'roles', 'isAdmin'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $roles            = Role::all();
        $structuralLevels = StructuralLevel::active()->get();
        $companies        = Company::where('id', auth()->user()->company_id)->get(['id', 'name']);
        return view('users.create', compact('roles', 'structuralLevels', 'companies'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $request->validate([
            'name'                => 'required|string|max:255',
            'email'               => 'required|email|unique:users',
            'password'            => 'required|min:8|confirmed',
            'role'                => 'required|exists:roles,name',
            'structural_level_id' => 'nullable|exists:structural_levels,id',
            'department_id'       => 'nullable|exists:departments,id',
        ]);

        $user = User::create([
            'name'                => $request->name,
            'email'               => $request->email,
            'password'            => $request->password,
            'company_id'          => auth()->user()->company_id,
            'is_active'           => $request->boolean('is_active', true),
            'structural_level_id' => $request->structural_level_id,
            'department_id'       => $request->department_id,
        ]);
        $user->assignRole($request->role);

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $roles            = Role::all();
        $structuralLevels = StructuralLevel::active()->get();
        $companies        = Company::where('id', auth()->user()->company_id)->get(['id', 'name']);

        // Pre-populate cascade dari department_id yang sudah ada
        $preselect = ['company_id' => null, 'branch_id' => null, 'division_id' => null];
        if ($user->department_id) {
            $dept = $user->department()->with('division.branch')->first();
            if ($dept) {
                $preselect['division_id'] = $dept->division_id;
                $preselect['branch_id']   = $dept->division?->branch_id;
                $preselect['company_id']  = $dept->division?->branch?->company_id;
            }
        }

        return view('users.edit', compact('user', 'roles', 'structuralLevels', 'companies', 'preselect'));
    }

    public function update(Request $request, User $user)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $request->validate([
            'name'                => 'required|string|max:255',
            'email'               => 'required|email|unique:users,email,' . $user->id,
            'role'                => 'required|exists:roles,name',
            'structural_level_id' => 'nullable|exists:structural_levels,id',
            'department_id'       => 'nullable|exists:departments,id',
        ]);

        $user->update([
            ...$request->only('name', 'email', 'timezone', 'structural_level_id', 'department_id'),
            'is_active' => $request->boolean('is_active'),
        ]);
        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')->with('success', 'User diperbarui.');
    }

    public function destroy(User $user)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }

        if ($user->id === auth()->id()) {
            return back()->withErrors(['Tidak bisa menghapus akun sendiri.']);
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User dihapus.');
    }
}
