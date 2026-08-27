<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\OrganizationUnit;
use App\Models\Package;
use App\Models\Project;
use App\Models\StructuralLevel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class SuperAdminController extends Controller
{
    use HasPerPage;

    public function dashboard(Request $request)
    {
        $stats = [
            'total_companies' => Company::count(),
            'total_users'     => User::where('is_super_admin', false)->count(),
            'total_projects'  => Project::count(),
            'new_this_month'  => Company::whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->count(),
        ];

        $companies = Company::withCount(['rootOrganizationUnits'])
            ->with(['organizationUnits.users' => fn($q) => $q->limit(1)])
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('superadmin.dashboard', compact('stats', 'companies'));
    }

    public function companies(Request $request)
    {
        $companies = Company::withCount(['rootOrganizationUnits'])
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('superadmin.companies', compact('companies'));
    }

    public function users(Request $request)
    {
        $users = User::with(['organizationUnit.company', 'company', 'additionalCompanies'])
            ->where('is_super_admin', false)
            ->when($request->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")))
            ->when($request->company_id, fn ($q) => $q->where('company_id', $request->company_id))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $companies = Company::orderBy('name')->get();

        return view('superadmin.users', compact('users', 'companies'));
    }

    /**
     * Atur company tambahan (di luar company utama) yang boleh diakses seorang
     * user non-superadmin, mis. supaya bisa mengelola Organization Units milik
     * beberapa company sekaligus.
     */
    public function updateUserCompanies(Request $request, User $user)
    {
        $request->validate([
            'companies'   => 'array',
            'companies.*' => 'exists:companies,id',
        ]);

        $additional = array_diff($request->input('companies', []), [$user->company_id]);
        $user->additionalCompanies()->sync($additional);

        return back()->with('success', "Akses company untuk {$user->name} berhasil diperbarui.");
    }

    public function toggleCompany(Company $company)
    {
        $company->update(['is_active' => !$company->is_active]);

        return back()->with('success', 'Status perusahaan diperbarui.');
    }

    /**
     * Hapus permanen perusahaan beserta seluruh user & data terkait (project, absensi,
     * payroll, dst). Dipakai superadmin untuk bersih-bersih data hasil tes register.
     * Urutan hapus penting: projects dulu (invoices.client_id tidak cascade dari user,
     * tapi cascade dari project), baru users, baru company (cascade ke organization_units).
     */
    public function destroyCompany(Request $request, Company $company)
    {
        $request->validate(['confirm_name' => 'required|string']);

        if (trim($request->confirm_name) !== $company->name) {
            return back()->withErrors(['confirm_name' => 'Nama perusahaan tidak cocok.']);
        }

        DB::transaction(function () use ($company) {
            Project::where('company_id', $company->id)->delete();
            User::where('company_id', $company->id)->delete();
            StructuralLevel::where('company_id', $company->id)->delete();
            $company->delete();
        });

        return redirect()->route('superadmin.companies')
            ->with('success', "Perusahaan {$company->name} beserta seluruh datanya berhasil dihapus.");
    }

    public function storeRegisteredUser(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:8',
            'company_name' => 'required|string|max:255',
            'packages'     => 'required|array|min:1',
            'packages.*'   => 'exists:packages,slug',
            'type'         => 'required|in:lifetime,expiry',
            'active_until' => 'required_if:type,expiry|nullable|date|after:today',
        ], [
            'email.unique'      => 'Email ini sudah terdaftar.',
            'packages.required' => 'Pilih minimal satu paket.',
        ]);

        DB::transaction(function () use ($request) {
            $company = Company::create([
                'name'      => $request->company_name,
                'code'      => Company::uniqueCodeFor($request->company_name),
                'is_active' => true,
            ]);

            $rootUnit = OrganizationUnit::create([
                'company_id' => $company->id,
                'name'       => 'Kantor Pusat',
                'is_active'  => true,
                ...OrganizationUnit::nextCodeForParent(null, $company->id),
            ]);

            $user = User::create([
                'name'                 => $request->name,
                'email'                => $request->email,
                'password'             => $request->password,
                'company_id'           => $company->id,
                'organization_unit_id' => $rootUnit->id,
                'is_active'            => true,
                'is_registered'        => true,
                'timezone'             => 'Asia/Jakarta',
                'active_until'         => $request->type === 'lifetime' ? null : $request->active_until,
            ]);

            $pkgIds = Package::whereIn('slug', $request->packages)->pluck('id');
            $user->packages()->sync($pkgIds);

            Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
            $user->assignRole('admin');
        });

        return back()->with('success', "Pelanggan {$request->name} berhasil ditambahkan.");
    }

    public function registeredUsers(Request $request)
    {
        $filter = $request->query('filter', 'all');

        $query = User::with(['organizationUnit.company', 'packages'])
            ->where('is_registered', true)
            ->latest();

        if ($filter === 'lifetime') {
            $query->whereNull('active_until');
        } elseif ($filter === 'expiring') {
            $query->whereNotNull('active_until')->where('active_until', '>', now());
        } elseif ($filter === 'expired') {
            $query->whereNotNull('active_until')->where('active_until', '<=', now());
        }

        $users = $query->paginate($this->perPage($request))->withQueryString();

        $counts = [
            'all'      => User::where('is_registered', true)->count(),
            'lifetime' => User::where('is_registered', true)->whereNull('active_until')->count(),
            'expiring' => User::where('is_registered', true)->whereNotNull('active_until')->where('active_until', '>', now())->count(),
            'expired'  => User::where('is_registered', true)->whereNotNull('active_until')->where('active_until', '<=', now())->count(),
        ];

        $packages = Package::active()->get();

        return view('superadmin.registered-users', compact('users', 'filter', 'counts', 'packages'));
    }

    public function updateLifetime(Request $request, User $user)
    {
        $request->validate([
            'type'         => 'required|in:lifetime,expiry',
            'active_until' => 'required_if:type,expiry|nullable|date|after:today',
        ]);

        $activeUntil = $request->type === 'lifetime' ? null : $request->active_until;

        $user->update(['active_until' => $activeUntil]);

        $msg = $request->type === 'lifetime'
            ? "Masa aktif {$user->name} diset ke Lifetime."
            : "Masa aktif {$user->name} diset hingga " . \Carbon\Carbon::parse($request->active_until)->format('d M Y') . '.';

        return back()->with('success', $msg);
    }

    public function packages()
    {
        $packages = Package::withCount('users')->with('features')
            ->orderBy('type')->orderBy('sort_order')->orderBy('name')
            ->get();

        return view('superadmin.packages', compact('packages'));
    }

    private function packageValidationRules(?Package $package = null): array
    {
        return [
            'type'            => 'required|in:tier,module',
            'slug'            => ['required', 'alpha_dash', 'max:50', Rule::unique('packages', 'slug')->ignore($package)],
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string',
            'tagline'         => 'nullable|string|max:255',
            'is_custom_price' => 'sometimes|boolean',
            'price'           => 'nullable|integer|min:0',
            'price_display'   => 'nullable|string|max:50',
            'price_period'    => 'nullable|string|max:100',
            'duration_days'   => 'nullable|integer|min:1',
            'is_custom_users' => 'sometimes|boolean',
            'max_users'       => 'nullable|integer|min:0',
            'is_active'       => 'sometimes|boolean',
            'is_popular'      => 'sometimes|boolean',
            'cta_label'       => 'nullable|string|max:100',
            'cta_type'        => 'required|in:register,contact',
            'sort_order'      => 'nullable|integer|min:0',
            'icon'            => 'nullable|string|max:30',
            'color'           => 'nullable|string|max:20',
            'fitur_text'      => 'nullable|string|max:255',
            'hris_feature'    => 'nullable|string|max:255',
            'features'        => 'nullable|array',
            'features.*'      => 'required|string|max:255',
        ];
    }

    /**
     * "Custom" (mis. paket Enterprise) ditandai dengan price = null, bukan 0 —
     * checkbox is_custom_price di form yang memaksa ini, angka di field price
     * diabaikan kalau dicentang.
     */
    private function packageDataFromRequest(Request $request): array
    {
        $data = $request->only([
            'type', 'slug', 'name', 'description', 'tagline',
            'price_display', 'price_period', 'duration_days',
            'cta_label', 'cta_type', 'sort_order',
            'icon', 'color', 'fitur_text', 'hris_feature',
        ]);

        $data['price']       = $request->boolean('is_custom_price') ? null : $request->input('price', 0);
        $data['max_users']   = $request->boolean('is_custom_users') ? null : $request->input('max_users', 0);
        $data['is_active']   = $request->boolean('is_active');
        $data['is_popular']  = $request->boolean('is_popular');
        $data['sort_order']  = $data['sort_order'] ?? 0;

        return $data;
    }

    private function syncPackageFeatures(Package $package, array $labels): void
    {
        $package->features()->delete();
        foreach (array_values($labels) as $i => $label) {
            $package->features()->create(['label' => $label, 'sort_order' => $i]);
        }
    }

    public function storePackage(Request $request)
    {
        $request->validate($this->packageValidationRules());

        $package = Package::create($this->packageDataFromRequest($request));
        $this->syncPackageFeatures($package, $request->input('features', []));

        return back()->with('success', "Paket {$package->name} berhasil dibuat.");
    }

    public function updatePackage(Request $request, Package $package)
    {
        $request->validate($this->packageValidationRules($package));

        $package->update($this->packageDataFromRequest($request));
        $this->syncPackageFeatures($package, $request->input('features', []));

        return back()->with('success', "Paket {$package->name} berhasil diperbarui.");
    }

    public function togglePackage(Package $package)
    {
        $package->update(['is_active' => !$package->is_active]);

        return back()->with('success', 'Status paket diperbarui.');
    }

    public function destroyPackage(Package $package)
    {
        if ($package->users()->exists()) {
            return back()->with('error', "Paket {$package->name} masih dipakai oleh pelanggan, tidak bisa dihapus. Nonaktifkan saja.");
        }

        $package->delete();

        return back()->with('success', "Paket {$package->name} berhasil dihapus.");
    }
}
