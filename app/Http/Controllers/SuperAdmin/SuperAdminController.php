<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

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

    public function registeredUsers(Request $request)
    {
        $filter = $request->query('filter', 'all');

        $query = User::with('department.division.branch.company')
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

        return view('superadmin.registered-users', compact('users', 'filter', 'counts'));
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
