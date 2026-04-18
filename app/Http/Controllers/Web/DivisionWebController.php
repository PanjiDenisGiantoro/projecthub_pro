<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use Illuminate\Http\Request;

class DivisionWebController extends Controller
{
    public function index(Request $request)
    {
        $divisions = Division::with('branch.company')
            ->withCount('departments')
            ->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->company_id, fn($q) => $q->whereHas('branch', fn($b) =>
                $b->where('company_id', $request->company_id)))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->has('is_active') && $request->is_active !== '', fn($q) =>
                $q->where('is_active', $request->boolean('is_active')))
            ->paginate(20);

        $companies = Company::orderBy('name')->get();
        $branches  = Branch::with('company')->orderBy('name')->get();

        return view('master.divisions.index', compact('divisions', 'companies', 'branches'));
    }

    public function create()
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $branches  = Branch::where('is_active', true)->with('company')->orderBy('name')->get();
        return view('master.divisions.create', compact('companies', 'branches'));
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
                return back()->withErrors(['code' => 'Kode sudah digunakan di branch ini.'])->withInput();
            }
        }

        Division::create($data);

        return redirect()->route('divisions.index')->with('success', 'Divisi berhasil ditambahkan.');
    }

    public function edit(Division $division)
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $branches  = Branch::where('is_active', true)->with('company')->orderBy('name')->get();
        return view('master.divisions.edit', compact('division', 'companies', 'branches'));
    }

    public function update(Request $request, Division $division)
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
                ->where('code', $data['code'])
                ->where('id', '!=', $division->id)->exists();
            if ($exists) {
                return back()->withErrors(['code' => 'Kode sudah digunakan di branch ini.'])->withInput();
            }
        }

        $division->update($data);

        return redirect()->route('divisions.index')->with('success', 'Divisi berhasil diperbarui.');
    }

    public function destroy(Division $division)
    {
        if ($division->departments()->exists()) {
            return back()->withErrors(['Tidak bisa menghapus divisi yang masih memiliki departemen.']);
        }
        $division->delete();
        return redirect()->route('divisions.index')->with('success', 'Divisi dihapus.');
    }
}
