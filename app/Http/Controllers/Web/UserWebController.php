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
        $users = User::with(['roles', 'structuralLevel', 'department'])
            ->when($request->role, fn($q) => $q->role($request->role))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")->orWhere('email', 'like', "%{$request->search}%"))
            ->paginate(20);
        $roles = Role::all();
        return view('users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles            = Role::all();
        $structuralLevels = StructuralLevel::active()->get();
        $companies        = Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('users.create', compact('roles', 'structuralLevels', 'companies'));
    }

    public function store(Request $request)
    {
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
            'is_active'           => $request->boolean('is_active', true),
            'structural_level_id' => $request->structural_level_id,
            'department_id'       => $request->department_id,
        ]);
        $user->assignRole($request->role);

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $roles            = Role::all();
        $structuralLevels = StructuralLevel::active()->get();
        $companies        = Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);

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
        $request->validate([
            'name'                => 'required|string|max:255',
            'email'               => 'required|email|unique:users,email,' . $user->id,
            'role'                => 'required|exists:roles,name',
            'structural_level_id' => 'nullable|exists:structural_levels,id',
            'department_id'       => 'nullable|exists:departments,id',
        ]);

        $user->update($request->only('name', 'email', 'is_active', 'timezone', 'structural_level_id', 'department_id'));
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
