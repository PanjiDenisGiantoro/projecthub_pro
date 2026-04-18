<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    public function index(Request $request)
    {
        $divisions = Division::with('branch.company')
            ->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->company_id, fn($q) => $q->whereHas('branch', fn($b) =>
                $b->where('company_id', $request->company_id)))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->has('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->withCount('departments')
            ->paginate(20);

        return response()->json($divisions);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id'   => 'required|exists:branches,id',
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        if (!empty($data['code'])) {
            $exists = Division::where('branch_id', $data['branch_id'])
                ->where('code', $data['code'])->exists();
            if ($exists) {
                return response()->json(['message' => 'Code already used in this branch.'], 422);
            }
        }

        $division = Division::create($data);

        return response()->json($division->load('branch.company'), 201);
    }

    public function show(Division $division)
    {
        return response()->json(
            $division->load(['branch.company', 'departments.head'])
                     ->loadCount('departments')
        );
    }

    public function update(Request $request, Division $division)
    {
        $data = $request->validate([
            'branch_id'   => 'sometimes|exists:branches,id',
            'name'        => 'sometimes|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active'   => 'sometimes|boolean',
        ]);

        $branchId = $data['branch_id'] ?? $division->branch_id;
        if (!empty($data['code'])) {
            $exists = Division::where('branch_id', $branchId)
                ->where('code', $data['code'])
                ->where('id', '!=', $division->id)->exists();
            if ($exists) {
                return response()->json(['message' => 'Code already used in this branch.'], 422);
            }
        }

        $division->update($data);

        return response()->json($division->load('branch.company'));
    }

    public function destroy(Division $division)
    {
        if ($division->departments()->exists()) {
            return response()->json(['message' => 'Cannot delete division that has departments.'], 422);
        }
        $division->delete();
        return response()->json(['message' => 'Division deleted.']);
    }
}
