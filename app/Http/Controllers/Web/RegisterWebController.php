<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
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
        ], [
            'email.unique'      => 'Email ini sudah terdaftar.',
            'password.min'      => 'Password minimal 8 karakter.',
            'password.confirmed'=> 'Konfirmasi password tidak cocok.',
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
                'department_id' => $department->id,
                'is_active'     => true,
                'is_registered' => true,
                'timezone'      => 'Asia/Jakarta',
            ]);

            Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
            $user->assignRole('admin');

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
