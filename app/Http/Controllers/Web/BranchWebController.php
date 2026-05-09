<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\Request;

class BranchWebController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::with('company')
            ->withCount(['divisions', 'departments'])
            ->when($request->company_id, fn($q) => $q->where('company_id', $request->company_id))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->has('is_active') && $request->is_active !== '', fn($q) =>
                $q->where('is_active', $request->boolean('is_active')))
            ->paginate(20);

        $companies = Company::orderBy('name')->get();

        return view('master.branches.index', compact('branches', 'companies'));
    }

    public function create()
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        return view('master.branches.create', compact('companies'));
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

        $data['is_active'] = $request->boolean('is_active');

        if (!empty($data['code'])) {
            $exists = Branch::where('company_id', $data['company_id'])
                ->where('code', $data['code'])->exists();
            if ($exists) {
                return back()->withErrors(['code' => 'Kode sudah digunakan di perusahaan ini.'])->withInput();
            }
        }

        Branch::create($data);

        return redirect()->route('branches.index')->with('success', 'Branch berhasil ditambahkan.');
    }

    public function edit(Branch $branch)
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        return view('master.branches.edit', compact('branch', 'companies'));
    }

    public function update(Request $request, Branch $branch)
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

        $data['is_active'] = $request->boolean('is_active');

        if (!empty($data['code'])) {
            $exists = Branch::where('company_id', $data['company_id'])
                ->where('code', $data['code'])
                ->where('id', '!=', $branch->id)->exists();
            if ($exists) {
                return back()->withErrors(['code' => 'Kode sudah digunakan di perusahaan ini.'])->withInput();
            }
        }

        $branch->update($data);

        return redirect()->route('branches.index')->with('success', 'Branch berhasil diperbarui.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->divisions()->exists()) {
            return back()->withErrors(['Tidak bisa menghapus branch yang masih memiliki divisi.']);
        }
        $branch->delete();
        return redirect()->route('branches.index')->with('success', 'Branch dihapus.');
    }
}
