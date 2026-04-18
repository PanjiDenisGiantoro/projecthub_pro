<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyWebController extends Controller
{
    public function index(Request $request)
    {
        $companies = Company::withCount(['divisions', 'departments'])
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->has('is_active') && $request->is_active !== '', fn($q) =>
                $q->where('is_active', $request->boolean('is_active')))
            ->paginate(12);

        return view('master.companies.index', compact('companies'));
    }

    public function create()
    {
        return view('master.companies.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'code'      => 'nullable|string|max:50|unique:companies,code',
            'address'   => 'nullable|string',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email',
            'is_active' => 'boolean',
        ]);

        Company::create($data);

        return redirect()->route('companies.index')->with('success', 'Perusahaan berhasil ditambahkan.');
    }

    public function edit(Company $company)
    {
        return view('master.companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'code'      => 'nullable|string|max:50|unique:companies,code,' . $company->id,
            'address'   => 'nullable|string',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email',
            'is_active' => 'boolean',
        ]);

        $company->update($data);

        return redirect()->route('companies.index')->with('success', 'Perusahaan berhasil diperbarui.');
    }

    public function destroy(Company $company)
    {
        if ($company->divisions()->exists()) {
            return back()->withErrors(['Tidak bisa menghapus perusahaan yang masih memiliki divisi.']);
        }
        $company->delete();
        return redirect()->route('companies.index')->with('success', 'Perusahaan dihapus.');
    }
}
