<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $companies = Company::query()
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->has('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->withCount(['divisions', 'departments'])
            ->paginate(20);

        return response()->json($companies);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'code'    => 'nullable|string|max:50|unique:companies,code',
            'address' => 'nullable|string',
            'phone'   => 'nullable|string|max:20',
            'email'   => 'nullable|email',
            'logo'    => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $company = Company::create($data);

        return response()->json($company, 201);
    }

    public function show(Company $company)
    {
        return response()->json(
            $company->loadCount(['divisions', 'departments'])
                    ->load('divisions.departments')
        );
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name'    => 'sometimes|string|max:255',
            'code'    => 'nullable|string|max:50|unique:companies,code,' . $company->id,
            'address' => 'nullable|string',
            'phone'   => 'nullable|string|max:20',
            'email'   => 'nullable|email',
            'logo'    => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $company->update($data);

        return response()->json($company);
    }

    public function destroy(Company $company)
    {
        if ($company->divisions()->exists()) {
            return response()->json(['message' => 'Cannot delete company that has divisions.'], 422);
        }

        $company->delete();

        return response()->json(['message' => 'Company deleted.']);
    }
}
