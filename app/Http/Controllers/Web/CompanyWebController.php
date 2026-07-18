<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyWebController extends Controller
{
    public function index(Request $request)
    {
        $cid = $this->tenantId();

        $companies = Company::withCount(['organizationUnits', 'rootOrganizationUnits'])
            ->when($cid, fn($q) => $q->where('id', $cid))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->has('is_active') && $request->is_active !== '', fn($q) =>
                $q->where('is_active', $request->boolean('is_active')))
            ->paginate(12);

        return view('master.companies.index', compact('companies'));
    }

    public function create()
    {
        // Tenant tidak boleh buat company baru (sudah punya 1)
        abort_if($this->tenantId() !== null, 403, 'Anda tidak dapat menambah perusahaan baru.');
        return view('master.companies.create');
    }

    public function store(Request $request)
    {
        abort_if($this->tenantId() !== null, 403);

        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'code'      => 'nullable|string|max:50|unique:companies,code',
            'address'   => 'nullable|string',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email',
            'website'   => 'nullable|url|max:255',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        Company::create($data);

        return redirect()->route('companies.index')->with('success', 'Perusahaan berhasil ditambahkan.');
    }

    public function edit(Company $company)
    {
        $this->authorizeCompany($company->id);
        return view('master.companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $this->authorizeCompany($company->id);

        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'code'      => 'nullable|string|max:50|unique:companies,code,' . $company->id,
            'address'   => 'nullable|string',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email',
            'website'   => 'nullable|url|max:255',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $company->update($data);

        return redirect()->route('companies.index')->with('success', 'Perusahaan berhasil diperbarui.');
    }

    public function destroy(Company $company)
    {
        abort_if($this->tenantId() !== null, 403, 'Tidak dapat menghapus perusahaan sendiri.');

        if ($company->organizationUnits()->exists()) {
            return back()->withErrors(['Tidak bisa menghapus perusahaan yang masih memiliki unit organisasi.']);
        }
        $company->delete();
        return redirect()->route('companies.index')->with('success', 'Perusahaan dihapus.');
    }
}
