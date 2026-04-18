<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['roles', 'department.division.branch.company'])
            ->when($request->role, fn($q) => $q->role($request->role))
            ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id))
            ->when($request->division_id, fn($q) => $q->whereHas('department', fn($d) =>
                $d->where('division_id', $request->division_id)))
            ->when($request->branch_id, fn($q) => $q->whereHas('department.division', fn($d) =>
                $d->where('branch_id', $request->branch_id)))
            ->when($request->company_id, fn($q) => $q->whereHas('department.division.branch', fn($b) =>
                $b->where('company_id', $request->company_id)))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->paginate(20);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users',
            'password'      => 'required|min:8',
            'role'          => 'required|exists:roles,name',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $user = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => $request->password,
            'is_active'     => $request->boolean('is_active', true),
            'timezone'      => $request->timezone ?? 'UTC',
            'department_id' => $request->department_id,
        ]);

        $user->assignRole($request->role);

        return response()->json($user->load(['roles', 'department.division.branch.company']), 201);
    }

    public function show(User $user)
    {
        return response()->json($user->load(['roles', 'department.division.branch.company']));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'          => 'sometimes|string|max:255',
            'email'         => 'sometimes|email|unique:users,email,' . $user->id,
            'role'          => 'sometimes|exists:roles,name',
            'is_active'     => 'sometimes|boolean',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $user->update($request->only('name', 'email', 'is_active', 'timezone', 'department_id'));

        if ($request->role) {
            $user->syncRoles([$request->role]);
        }

        return response()->json($user->load(['roles', 'department.division.branch.company']));
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot delete your own account.'], 422);
        }
        $user->delete();
        return response()->json(['message' => 'User deleted.']);
    }

    public function roles()
    {
        return response()->json(Role::all());
    }
}
