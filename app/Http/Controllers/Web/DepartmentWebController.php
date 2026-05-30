<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\User;
use Illuminate\Http\Request;

class DepartmentWebController extends Controller
{
    public function index(Request $request)
    {
        $cid = $this->tenantId();

        $departments = Department::with(['division.branch.company', 'head'])
            ->withCount('users')
            ->when($cid, fn($q) => $q->whereHas('division.branch', fn($b) => $b->where('company_id', $cid)))
            ->when(! $cid && $request->division_id, fn($q) => $q->where('division_id', $request->division_id))
            ->when(! $cid && $request->branch_id, fn($q) => $q->whereHas('division', fn($d) =>
                $d->where('branch_id', $request->branch_id)))
            ->when(! $cid && $request->company_id, fn($q) => $q->whereHas('division.branch', fn($b) =>
                $b->where('company_id', $request->company_id)))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->has('is_active') && $request->is_active !== '', fn($q) =>
                $q->where('is_active', $request->boolean('is_active')))
            ->paginate(20);

        $companies = $cid ? Company::where('id', $cid)->get() : Company::orderBy('name')->get();
        $branches  = $cid
            ? Branch::where('company_id', $cid)->with('company')->orderBy('name')->get()
            : Branch::with('company')->orderBy('name')->get();
        $divisions = $cid
            ? Division::whereHas('branch', fn($b) => $b->where('company_id', $cid))->with('branch.company')->orderBy('name')->get()
            : Division::with('branch.company')->orderBy('name')->get();

        return view('master.departments.index', compact('departments', 'companies', 'branches', 'divisions'));
    }

    public function create(Request $request)
    {
        $cid = $this->tenantId();

        $companies = $cid ? Company::where('id', $cid)->get() : Company::where('is_active', true)->orderBy('name')->get();
        $branches  = $cid
            ? Branch::where('company_id', $cid)->where('is_active', true)->with('company')->orderBy('name')->get()
            : Branch::where('is_active', true)->with('company')->orderBy('name')->get();
        $divisions = $cid
            ? Division::whereHas('branch', fn($b) => $b->where('company_id', $cid))->where('is_active', true)->with('branch.company')->orderBy('name')->get()
            : Division::where('is_active', true)->with('branch.company')->orderBy('name')->get();
        $users = $cid
            ? User::where('company_id', $cid)->where('is_active', true)->orderBy('name')->get()
            : User::where('is_active', true)->orderBy('name')->get();

        $selectedCompany = $request->company_id;
        $selectedBranch  = $request->branch_id;

        return view('master.departments.create', compact('companies', 'branches', 'divisions', 'users', 'selectedCompany', 'selectedBranch'));
    }

    public function store(Request $request)
    {
        $cid = $this->tenantId();

        $data = $request->validate([
            'division_id' => 'required|exists:divisions,id',
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'head_id'     => 'nullable|exists:users,id',
            'is_active'   => 'boolean',
        ]);

        if ($cid) {
            $division = Division::with('branch')->findOrFail($data['division_id']);
            if ($division->branch->company_id !== $cid) abort(403);
        }

        $data['is_active'] = $request->boolean('is_active');

        if (! empty($data['code'])) {
            $exists = Department::where('division_id', $data['division_id'])
                ->where('code', $data['code'])->exists();
            if ($exists) {
                return back()->withErrors(['code' => 'Kode sudah digunakan di divisi ini.'])->withInput();
            }
        }

        Department::create($data);
        return redirect()->route('departments.index')->with('success', 'Departemen berhasil ditambahkan.');
    }

    public function edit(Department $department)
    {
        $this->authorizeCompany($department->division->branch->company_id);

        $cid = $this->tenantId();

        $companies = $cid ? Company::where('id', $cid)->get() : Company::where('is_active', true)->orderBy('name')->get();
        $branches  = $cid
            ? Branch::where('company_id', $cid)->where('is_active', true)->with('company')->orderBy('name')->get()
            : Branch::where('is_active', true)->with('company')->orderBy('name')->get();
        $divisions = $cid
            ? Division::whereHas('branch', fn($b) => $b->where('company_id', $cid))->where('is_active', true)->with('branch.company')->orderBy('name')->get()
            : Division::where('is_active', true)->with('branch.company')->orderBy('name')->get();
        $users = $cid
            ? User::where('company_id', $cid)->where('is_active', true)->orderBy('name')->get()
            : User::where('is_active', true)->orderBy('name')->get();

        return view('master.departments.edit', compact('department', 'companies', 'branches', 'divisions', 'users'));
    }

    public function update(Request $request, Department $department)
    {
        $this->authorizeCompany($department->division->branch->company_id);

        $cid = $this->tenantId();

        $data = $request->validate([
            'division_id' => 'required|exists:divisions,id',
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'head_id'     => 'nullable|exists:users,id',
            'is_active'   => 'boolean',
        ]);

        if ($cid) {
            $division = Division::with('branch')->findOrFail($data['division_id']);
            if ($division->branch->company_id !== $cid) abort(403);
        }

        $data['is_active'] = $request->boolean('is_active');

        if (! empty($data['code'])) {
            $exists = Department::where('division_id', $data['division_id'])
                ->where('code', $data['code'])
                ->where('id', '!=', $department->id)->exists();
            if ($exists) {
                return back()->withErrors(['code' => 'Kode sudah digunakan di divisi ini.'])->withInput();
            }
        }

        $department->update($data);
        return redirect()->route('departments.index')->with('success', 'Departemen berhasil diperbarui.');
    }

    public function destroy(Department $department)
    {
        $this->authorizeCompany($department->division->branch->company_id);

        if ($department->users()->exists()) {
            return back()->withErrors(['Tidak bisa menghapus departemen yang masih memiliki anggota.']);
        }
        $department->delete();
        return redirect()->route('departments.index')->with('success', 'Departemen dihapus.');
    }
}
