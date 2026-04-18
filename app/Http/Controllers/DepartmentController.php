<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::with(['division.branch.company', 'head'])
            ->when($request->division_id, fn($q) => $q->where('division_id', $request->division_id))
            ->when($request->branch_id, fn($q) => $q->whereHas('division', fn($d) =>
                $d->where('branch_id', $request->branch_id)))
            ->when($request->company_id, fn($q) => $q->whereHas('division.branch', fn($b) =>
                $b->where('company_id', $request->company_id)))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->has('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->withCount('users')
            ->paginate(20);

        return response()->json($departments);
    }

    public function options()
    {
        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'division_id']);

        return response()->json($departments);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'division_id' => 'required|exists:divisions,id',
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'head_id'     => 'nullable|exists:users,id',
            'is_active'   => 'boolean',
        ]);

        // Unique code per division
        if (!empty($data['code'])) {
            $exists = Department::where('division_id', $data['division_id'])
                ->where('code', $data['code'])
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'Code already used in this division.'], 422);
            }
        }

        $department = Department::create($data);

        return response()->json($department->load(['division.company', 'head']), 201);
    }

    public function show(Department $department)
    {
        return response()->json(
            $department->load(['division.company', 'head', 'users'])
                       ->loadCount('users')
        );
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'division_id' => 'sometimes|exists:divisions,id',
            'name'        => 'sometimes|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'head_id'     => 'nullable|exists:users,id',
            'is_active'   => 'sometimes|boolean',
        ]);

        // Unique code per division (exclude self)
        $divisionId = $data['division_id'] ?? $department->division_id;
        if (!empty($data['code'])) {
            $exists = Department::where('division_id', $divisionId)
                ->where('code', $data['code'])
                ->where('id', '!=', $department->id)
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'Code already used in this division.'], 422);
            }
        }

        $department->update($data);

        return response()->json($department->load(['division.company', 'head']));
    }

    public function destroy(Department $department)
    {
        if ($department->users()->exists()) {
            return response()->json(['message' => 'Cannot delete department that still has users.'], 422);
        }

        $department->delete();

        return response()->json(['message' => 'Department deleted.']);
    }
}
