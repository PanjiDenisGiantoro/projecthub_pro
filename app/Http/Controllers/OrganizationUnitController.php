<?php

namespace App\Http\Controllers;

use App\Models\OrganizationUnit;
use Illuminate\Http\Request;

class OrganizationUnitController extends Controller
{
    public function options(Request $request)
    {
        $units = OrganizationUnit::where('is_active', true)
            ->when($request->company_id, fn($q) => $q->where('company_id', $request->company_id))
            ->orderBy('order')
            ->get(['id', 'name', 'code', 'level', 'parent_id']);

        return response()->json($units);
    }

    public function index(Request $request)
    {
        $units = OrganizationUnit::with(['company', 'parent', 'head'])
            ->when($request->company_id, fn($q) => $q->where('company_id', $request->company_id))
            ->when($request->parent_id, fn($q) => $q->where('parent_id', $request->parent_id))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->has('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->withCount(['children', 'users'])
            ->orderBy('code')
            ->paginate(20);

        return response()->json($units);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'parent_id'  => 'nullable|exists:organization_units,id',
            'name'       => 'required|string|max:255',
            'head_id'    => 'nullable|exists:users,id',
            'is_active'  => 'boolean',
        ]);

        if (! empty($data['parent_id'])) {
            $parent = OrganizationUnit::findOrFail($data['parent_id']);
            if ($parent->company_id !== (int) $data['company_id']) {
                return response()->json(['message' => 'Parent unit must belong to the same company.'], 422);
            }
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $generated = OrganizationUnit::nextCodeForParent($data['parent_id'] ?? null, $data['company_id']);

        $unit = OrganizationUnit::create([...$data, ...$generated]);

        return response()->json($unit->load(['company', 'parent', 'head']), 201);
    }

    public function show(OrganizationUnit $organizationUnit)
    {
        return response()->json(
            $organizationUnit->load(['company', 'parent', 'children', 'head', 'users'])
                ->loadCount(['children', 'users'])
        );
    }

    public function update(Request $request, OrganizationUnit $organizationUnit)
    {
        $data = $request->validate([
            'parent_id' => 'nullable|exists:organization_units,id',
            'name'      => 'sometimes|string|max:255',
            'head_id'   => 'nullable|exists:users,id',
            'is_active' => 'sometimes|boolean',
        ]);

        if (array_key_exists('parent_id', $data) && (int) $data['parent_id'] === $organizationUnit->id) {
            return response()->json(['message' => 'A unit cannot be its own parent.'], 422);
        }

        $parentChanged = array_key_exists('parent_id', $data)
            && $data['parent_id'] != $organizationUnit->parent_id;

        if ($parentChanged) {
            if (! empty($data['parent_id'])) {
                $parent = OrganizationUnit::findOrFail($data['parent_id']);
                if ($parent->company_id !== $organizationUnit->company_id) {
                    return response()->json(['message' => 'Parent unit must belong to the same company.'], 422);
                }
                if (str_starts_with($parent->code . '.', $organizationUnit->code . '.')) {
                    return response()->json(['message' => 'Cannot move a unit under its own descendant.'], 422);
                }
            }
            $generated = OrganizationUnit::nextCodeForParent($data['parent_id'] ?? null, $organizationUnit->company_id);
            $data = [...$data, ...$generated];
        }

        $organizationUnit->update($data);

        if ($parentChanged) {
            $organizationUnit->regenerateDescendantCodes();
        }

        return response()->json($organizationUnit->load(['company', 'parent', 'head']));
    }

    public function destroy(OrganizationUnit $organizationUnit)
    {
        if ($organizationUnit->children()->exists()) {
            return response()->json(['message' => 'Cannot delete a unit that still has child units.'], 422);
        }
        if ($organizationUnit->users()->exists()) {
            return response()->json(['message' => 'Cannot delete a unit that still has users.'], 422);
        }

        $organizationUnit->delete();

        return response()->json(['message' => 'Organization unit deleted.']);
    }
}
