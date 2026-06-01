<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\LeaveType;
use App\Models\OvertimeRule;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RegisterWebController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'company_name' => 'required|string|max:255',
            'password'     => 'required|string|min:8|confirmed',
            'packages'     => 'required|array|min:1',
            'packages.*'   => 'in:task_management,hris',
        ], [
            'email.unique'       => 'Email ini sudah terdaftar.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'packages.required'  => 'Pilih minimal satu paket aplikasi.',
            'packages.min'       => 'Pilih minimal satu paket aplikasi.',
        ]);

        $user = DB::transaction(function () use ($request) {
            $company = Company::create([
                'name'      => $request->company_name,
                'code'      => Str::upper(Str::slug($request->company_name, '')),
                'is_active' => true,
            ]);

            $branch = Branch::create([
                'company_id' => $company->id,
                'name'       => 'Kantor Pusat',
                'code'       => 'PUSAT',
                'is_active'  => true,
            ]);

            $division = Division::create([
                'branch_id'  => $branch->id,
                'name'       => 'Umum',
                'code'       => 'UMUM',
                'is_active'  => true,
            ]);

            $department = Department::create([
                'division_id' => $division->id,
                'name'        => 'Manajemen',
                'code'        => 'MGT',
                'is_active'   => true,
            ]);

            $user = User::create([
                'name'          => $request->name,
                'email'         => $request->email,
                'password'      => $request->password,
                'company_id'    => $company->id,
                'department_id' => $department->id,
                'is_active'     => true,
                'is_registered' => true,
                'timezone'      => 'Asia/Jakarta',
            ]);

            $pkgIds = Package::whereIn('slug', $request->packages)->pluck('id');
            $user->packages()->sync($pkgIds);

            // Clone HRIS master templates for this company
            LeaveType::whereNull('company_id')->get()->each(function ($t) use ($company) {
                $data = collect($t->toArray())->except(['id', 'company_id', 'created_at', 'updated_at'])->toArray();
                LeaveType::updateOrCreate(['company_id' => $company->id, 'code' => $t->code], $data);
            });
            OvertimeRule::whereNull('company_id')->get()->each(function ($r) use ($company) {
                $data = collect($r->toArray())->except(['id', 'company_id', 'created_at', 'updated_at'])->toArray();
                OvertimeRule::updateOrCreate(
                    ['company_id' => $company->id, 'day_type' => $r->day_type, 'hour_from' => $r->hour_from, 'hour_to' => $r->hour_to],
                    $data
                );
            });

            Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
            $user->assignRole('admin');

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $firstPkg = $user->packages()->active()->first();
        $request->session()->put('active_package', $firstPkg?->slug ?? 'task_management');

        return redirect()->route('dashboard');
    }
}
