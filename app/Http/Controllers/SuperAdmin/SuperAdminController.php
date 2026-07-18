<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\OrganizationUnit;
use App\Models\Package;
use App\Models\Project;
use App\Models\StructuralLevel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class SuperAdminController extends Controller
{
    public function dashboard()
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
            ->paginate(15);

        return view('superadmin.dashboard', compact('stats', 'companies'));
    }

    public function companies()
    {
        $companies = Company::withCount(['rootOrganizationUnits'])
            ->latest()
            ->paginate(20);

        return view('superadmin.companies', compact('companies'));
    }

    public function users()
    {
        $users = User::with('organizationUnit.company')
            ->where('is_super_admin', false)
            ->latest()
            ->paginate(20);

        return view('superadmin.users', compact('users'));
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

        $users = $query->paginate(20)->withQueryString();

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
}
