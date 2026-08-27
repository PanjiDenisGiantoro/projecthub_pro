<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['roles', 'organizationUnit.company'])
            ->when($request->role, fn($q) => $q->role($request->role))
            ->when($request->organization_unit_id, fn($q) => $q->where('organization_unit_id', $request->organization_unit_id))
            ->when($request->company_id, fn($q) => $q->whereHas('organizationUnit', fn($u) =>
                $u->where('company_id', $request->company_id)))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->paginate(20);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                 => 'required|string|max:255',
            'email'                => 'required|email|unique:users',
            'password'             => 'required|min:8',
            'role'                 => 'required|exists:roles,name',
            'organization_unit_id' => 'nullable|exists:organization_units,id',
        ]);

        $user = User::create([
            'name'                 => $request->name,
            'email'                => $request->email,
            'password'             => $request->password,
            'is_active'            => $request->boolean('is_active', true),
            'timezone'             => $request->timezone ?? 'UTC',
            'organization_unit_id' => $request->organization_unit_id,
        ]);

        $user->assignRole($request->role);

        return response()->json($user->load(['roles', 'organizationUnit.company']), 201);
    }

    public function show(User $user)
    {
        return response()->json($user->load(['roles', 'organizationUnit.company']));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'                 => 'sometimes|string|max:255',
            'email'                => 'sometimes|email|unique:users,email,' . $user->id,
            'role'                 => 'sometimes|exists:roles,name',
            'is_active'            => 'sometimes|boolean',
            'organization_unit_id' => 'nullable|exists:organization_units,id',
        ]);

        $user->update($request->only('name', 'email', 'is_active', 'timezone', 'organization_unit_id'));

        if ($request->role) {
            $user->syncRoles([$request->role]);
        }

        return response()->json($user->load(['roles', 'organizationUnit.company']));
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
