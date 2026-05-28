<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Project;
use App\Models\User;

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

        $companies = Company::withCount(['branches'])
            ->with(['branches.divisions.departments.users' => fn($q) => $q->limit(1)])
            ->latest()
            ->paginate(15);

        return view('superadmin.dashboard', compact('stats', 'companies'));
    }

    public function companies()
    {
        $companies = Company::withCount(['branches'])
            ->latest()
            ->paginate(20);

        return view('superadmin.companies', compact('companies'));
    }

    public function users()
    {
        $users = User::with('department.division.branch.company')
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
}
