<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::with('company')
            ->when($request->company_id, fn($q) => $q->where('company_id', $request->company_id))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->has('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->withCount(['divisions', 'departments'])
            ->paginate(20);

        return response()->json($branches);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name'       => 'required|string|max:255',
            'code'       => 'nullable|string|max:50',
            'address'    => 'nullable|string',
            'phone'      => 'nullable|string|max:20',
            'email'      => 'nullable|email',
            'is_active'  => 'boolean',
        ]);

        if (!empty($data['code'])) {
            $exists = Branch::where('company_id', $data['company_id'])
                ->where('code', $data['code'])->exists();
            if ($exists) {
                return response()->json(['message' => 'Code already used in this company.'], 422);
            }
        }

        $branch = Branch::create($data);

        return response()->json($branch->load('company'), 201);
    }

    public function show(Branch $branch)
    {
        return response()->json(
            $branch->load(['company', 'divisions.departments'])
                   ->loadCount(['divisions', 'departments'])
        );
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'company_id' => 'sometimes|exists:companies,id',
            'name'       => 'sometimes|string|max:255',
            'code'       => 'nullable|string|max:50',
            'address'    => 'nullable|string',
            'phone'      => 'nullable|string|max:20',
            'email'      => 'nullable|email',
            'is_active'  => 'sometimes|boolean',
        ]);

        $companyId = $data['company_id'] ?? $branch->company_id;
        if (!empty($data['code'])) {
            $exists = Branch::where('company_id', $companyId)
                ->where('code', $data['code'])
                ->where('id', '!=', $branch->id)->exists();
            if ($exists) {
                return response()->json(['message' => 'Code already used in this company.'], 422);
            }
        }

        $branch->update($data);

        return response()->json($branch->load('company'));
    }

    public function destroy(Branch $branch)
    {
        if ($branch->divisions()->exists()) {
            return response()->json(['message' => 'Cannot delete branch that has divisions.'], 422);
        }
        $branch->delete();
        return response()->json(['message' => 'Branch deleted.']);
    }
}
