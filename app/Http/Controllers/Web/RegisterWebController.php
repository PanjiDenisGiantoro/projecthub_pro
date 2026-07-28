<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\AccountCredentialsMail;
use App\Models\Company;
use App\Models\LeaveType;
use App\Models\OrganizationUnit;
use App\Models\OvertimeRule;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class RegisterWebController extends Controller
{
    public function show(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register', [
            'prefillName'  => $request->query('name'),
            'prefillEmail' => $request->query('email'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'company_name' => 'required|string|max:255',
            'password'     => 'required|string|min:8|confirmed',
            'plan'         => 'required|in:starter,pro',
        ], [
            'email.unique'       => 'Email ini sudah terdaftar.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user = DB::transaction(function () use ($request) {
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
                // Starter = gratis selamanya (active_until null = lifetime).
                // Pro = belum bayar, langsung diarahkan ke Midtrans setelah akun dibuat
                // (lihat bawah); active_until di masa lalu supaya CheckActiveAccess
                // memaksa ke billing.renew kalau pembayaran belum selesai/dibatalkan.
                'active_until'         => $request->plan === 'pro' ? now()->subMinute() : null,
            ]);

            // Semua paket mendaftar dengan modul Task Management & HRIS aktif.
            $pkgIds = Package::whereIn('slug', ['task_management', 'hris'])->pluck('id');
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

        $verificationUrl = \App\Notifications\QueuedVerifyEmail::urlFor($user);
        Mail::to($user->email)->send(new AccountCredentialsMail($user, $request->password, $verificationUrl));

        if ($request->plan === 'pro') {
            $proPackage = Package::where('slug', 'pro')->firstOrFail();

            Auth::login($user);

            return app(BillingWebController::class)->checkout($request, $proPackage);
        }

        return redirect()->route('login')
            ->with('status', 'Pendaftaran berhasil! Silakan login dengan email dan password Anda, lalu verifikasi email untuk mengaktifkan akun.');
    }
}
