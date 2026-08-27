<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\OrganizationUnit;
use App\Models\User;
use Illuminate\Http\Request;

class OrganizationUnitWebController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $companies = $user->is_super_admin
            ? Company::orderBy('name')->get()
            : $user->accessibleCompanies();

        $requested = $request->integer('company_id') ?: null;
        $selectedCompany = ($requested && ($user->is_super_admin || $user->canAccessCompany($requested)))
            ? $requested
            : $companies->first()?->id;

        $units = collect();
        if ($selectedCompany) {
            $units = OrganizationUnit::orderedTree($selectedCompany)
                ->loadMissing(['head', 'company'])
                ->loadCount(['children', 'users'])
                ->when($request->search, fn($c) => $c->filter(fn($u) =>
                    str_contains(strtolower($u->name), strtolower($request->search))
                    || str_contains(strtolower($u->code), strtolower($request->search))))
                ->when($request->has('is_active') && $request->is_active !== '', fn($c) =>
                    $c->where('is_active', $request->boolean('is_active')));
        }

        return view('master.organization-units.index', compact('units', 'companies', 'selectedCompany'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();

        $companies = $user->is_super_admin
            ? Company::where('is_active', true)->orderBy('name')->get()
            : $user->accessibleCompanies();

        $requested = $request->integer('company_id') ?: null;
        $selectedCompany = ($requested && ($user->is_super_admin || $user->canAccessCompany($requested)))
            ? $requested
            : ($companies->count() === 1 ? $companies->first()->id : null);

        $tree  = $this->treeForCompany($selectedCompany);
        $users = $this->activeUsersForCompany($selectedCompany);

        return view('master.organization-units.create', compact('companies', 'tree', 'users', 'selectedCompany'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'parent_id'  => 'nullable|exists:organization_units,id',
            'name'       => 'required|string|max:255',
            'head_id'    => 'nullable|exists:users,id',
            'is_active'  => 'boolean',
            'color'      => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        if (! $user->is_super_admin && ! $user->canAccessCompany((int) $data['company_id'])) {
            abort(403);
        }

        if (! empty($data['parent_id'])) {
            $parent = OrganizationUnit::findOrFail($data['parent_id']);
            if ($parent->company_id !== (int) $data['company_id']) {
                abort(403);
            }
        }

        $data['is_active'] = $request->boolean('is_active');
        $generated = OrganizationUnit::nextCodeForParent($data['parent_id'] ?? null, $data['company_id']);

        OrganizationUnit::create([...$data, ...$generated]);

        return redirect()->route('organization-units.index')->with('success', 'Unit organisasi berhasil ditambahkan.');
    }

    public function edit(OrganizationUnit $organizationUnit)
    {
        $this->authorizeCompanyAccess($organizationUnit->company_id);

        $user = auth()->user();

        $companies = $user->is_super_admin
            ? Company::where('is_active', true)->orderBy('name')->get()
            : $user->accessibleCompanies();

        $tree  = $this->treeForCompany($organizationUnit->company_id, exclude: $organizationUnit);
        $users = $this->activeUsersForCompany($organizationUnit->company_id);

        return view('master.organization-units.edit', compact('organizationUnit', 'companies', 'tree', 'users'));
    }

    public function update(Request $request, OrganizationUnit $organizationUnit)
    {
        $this->authorizeCompanyAccess($organizationUnit->company_id);

        $data = $request->validate([
            'parent_id' => 'nullable|exists:organization_units,id',
            'name'      => 'required|string|max:255',
            'head_id'   => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'color'     => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if ((int) ($data['parent_id'] ?? 0) === $organizationUnit->id) {
            return back()->withErrors(['parent_id' => 'Unit tidak bisa jadi parent dirinya sendiri.'])->withInput();
        }

        $parentChanged = ($data['parent_id'] ?? null) != $organizationUnit->parent_id;

        if ($parentChanged && ! empty($data['parent_id'])) {
            $parent = OrganizationUnit::findOrFail($data['parent_id']);
            if ($parent->company_id !== $organizationUnit->company_id) {
                abort(403);
            }
            if (str_starts_with($parent->code . '.', $organizationUnit->code . '.')) {
                return back()->withErrors(['parent_id' => 'Tidak bisa memindahkan unit ke bawah anaknya sendiri.'])->withInput();
            }
        }

        if ($parentChanged) {
            $generated = OrganizationUnit::nextCodeForParent($data['parent_id'] ?? null, $organizationUnit->company_id);
            $data = [...$data, ...$generated];
        }

        $organizationUnit->update($data);

        if ($parentChanged) {
            $organizationUnit->regenerateDescendantCodes();
        }

        return redirect()->route('organization-units.index')->with('success', 'Unit organisasi berhasil diperbarui.');
    }

    public function destroy(OrganizationUnit $organizationUnit)
    {
        $this->authorizeCompanyAccess($organizationUnit->company_id);

        if ($organizationUnit->children()->exists()) {
            return back()->withErrors(['Tidak bisa menghapus unit yang masih memiliki unit turunan.']);
        }
        if ($organizationUnit->users()->exists()) {
            return back()->withErrors(['Tidak bisa menghapus unit yang masih memiliki anggota.']);
        }

        $organizationUnit->delete();

        return redirect()->route('organization-units.index')->with('success', 'Unit organisasi dihapus.');
    }

    /**
     * Sama seperti authorizeCompany(), tapi juga mengizinkan company tambahan
     * (lihat User::accessibleCompanies()), bukan cuma company utama user.
     */
    private function authorizeCompanyAccess(int $companyId): void
    {
        $user = auth()->user();
        if (! $user->is_super_admin && ! $user->canAccessCompany($companyId)) {
            abort(403);
        }
    }

    /** Seluruh unit se-company dalam urutan DFS, untuk render <select> parent berindentasi. */
    private function treeForCompany(?int $companyId, ?OrganizationUnit $exclude = null)
    {
        if (! $companyId) {
            return collect();
        }

        $exceptIds = $exclude
            ? [$exclude->id, ...$exclude->descendants()->pluck('id')->all()]
            : [];

        return OrganizationUnit::orderedTree($companyId, $exceptIds);
    }

    private function activeUsersForCompany(?int $companyId)
    {
        if (! $companyId) {
            return collect();
        }

        return User::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
    }
}
