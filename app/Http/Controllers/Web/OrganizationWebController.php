<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

class OrganizationWebController extends Controller
{
    public function index(Request $request)
    {
        $cid = $this->tenantId();

        $companies = $cid ? Company::where('id', $cid)->get() : Company::orderBy('name')->get();
        $selectedCompanyId = $cid ?: ($request->company_id ?: optional($companies->first())->id);

        $nodes = $selectedCompanyId
            ? Organization::with('head')
                ->where('company_id', $selectedCompanyId)
                ->orderBy('sort_order')->orderBy('name')
                ->get()
            : collect();

        $tree = $this->buildTree($nodes);

        return view('master.organizations.index', compact('companies', 'selectedCompanyId', 'tree'));
    }

    private function buildTree($nodes, $parentId = null)
    {
        return $nodes->where('parent_id', $parentId)->map(function ($node) use ($nodes) {
            $node->setRelation('childNodes', $this->buildTree($nodes, $node->id));
            return $node;
        })->values();
    }

    public function create(Request $request)
    {
        $cid = $this->tenantId();

        $companies = $cid ? Company::where('id', $cid)->get() : Company::where('is_active', true)->orderBy('name')->get();
        $companyId = $cid ?: $request->company_id;

        $parents = $companyId
            ? Organization::where('company_id', $companyId)->orderBy('name')->get()
            : collect();
        $users = $companyId
            ? User::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get()
            : collect();

        $selectedParent = $request->parent_id;

        return view('master.organizations.create', compact('companies', 'parents', 'users', 'companyId', 'cid', 'selectedParent'));
    }

    public function store(Request $request)
    {
        $cid = $this->tenantId();

        $data = $request->validate([
            'company_id'  => $cid ? 'nullable' : 'required|exists:companies,id',
            'parent_id'   => 'nullable|exists:organizations,id',
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'head_id'     => 'nullable|exists:users,id',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ]);

        $data['company_id'] = $cid ?: $data['company_id'];

        if ($data['parent_id'] ?? null) {
            $parent = Organization::findOrFail($data['parent_id']);
            if ($parent->company_id !== $data['company_id']) abort(403);
        }

        if (! empty($data['code'])) {
            $exists = Organization::where('company_id', $data['company_id'])
                ->where('parent_id', $data['parent_id'] ?? null)
                ->where('code', $data['code'])->exists();
            if ($exists) {
                return back()->withErrors(['code' => 'Kode sudah digunakan di level yang sama.'])->withInput();
            }
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        Organization::create($data);
        return redirect()->route('organizations.index', ['company_id' => $data['company_id']])->with('success', 'Unit organisasi berhasil ditambahkan.');
    }

    public function edit(Organization $organization)
    {
        $this->authorizeCompany($organization->company_id);

        $cid = $this->tenantId();
        $companies = $cid ? Company::where('id', $cid)->get() : Company::orderBy('name')->get();

        $excluded = array_merge([$organization->id], $organization->descendantIds());
        $parents = Organization::where('company_id', $organization->company_id)
            ->whereNotIn('id', $excluded)
            ->orderBy('name')->get();
        $users = User::where('company_id', $organization->company_id)->where('is_active', true)->orderBy('name')->get();

        return view('master.organizations.edit', compact('organization', 'companies', 'parents', 'users', 'cid'));
    }

    public function update(Request $request, Organization $organization)
    {
        $this->authorizeCompany($organization->company_id);

        $data = $request->validate([
            'parent_id'   => 'nullable|exists:organizations,id',
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'head_id'     => 'nullable|exists:users,id',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ]);

        if ($data['parent_id'] ?? null) {
            if ((int) $data['parent_id'] === $organization->id || in_array((int) $data['parent_id'], $organization->descendantIds())) {
                return back()->withErrors(['parent_id' => 'Parent tidak boleh diri sendiri atau keturunannya sendiri.'])->withInput();
            }
            $parent = Organization::findOrFail($data['parent_id']);
            if ($parent->company_id !== $organization->company_id) abort(403);
        }

        if (! empty($data['code'])) {
            $exists = Organization::where('company_id', $organization->company_id)
                ->where('parent_id', $data['parent_id'] ?? null)
                ->where('code', $data['code'])
                ->where('id', '!=', $organization->id)->exists();
            if ($exists) {
                return back()->withErrors(['code' => 'Kode sudah digunakan di level yang sama.'])->withInput();
            }
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $organization->update($data);
        return redirect()->route('organizations.index', ['company_id' => $organization->company_id])->with('success', 'Unit organisasi berhasil diperbarui.');
    }

    public function destroy(Organization $organization)
    {
        $this->authorizeCompany($organization->company_id);

        if ($organization->children()->exists()) {
            return back()->withErrors(['Tidak bisa menghapus unit yang masih memiliki sub-unit. Hapus atau pindahkan sub-unit itu terlebih dahulu.']);
        }

        $organization->delete();
        return redirect()->route('organizations.index', ['company_id' => $organization->company_id])->with('success', 'Unit organisasi dihapus.');
    }
}
