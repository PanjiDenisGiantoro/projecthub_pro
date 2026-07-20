<?php

namespace App\Http\Controllers\Web\Hris;

use App\Http\Controllers\Controller;
use App\Models\EmployeeSalary;
use App\Models\TaxPtkp;
use App\Models\User;
use Illuminate\Http\Request;

class EmployeeSalaryController extends Controller
{
    public function index(User $user)
    {
        $this->authorize('view payroll');
        abort_if($user->company_id !== auth()->user()->company_id, 403);

        $salaries   = $user->salaries()->orderByDesc('effective_date')->get();
        $statusOptions = TaxPtkp::forSelect();

        return view('hris.salary.index', compact('user', 'salaries', 'statusOptions'));
    }

    public function create(User $user)
    {
        $this->authorize('create payroll');
        abort_if($user->company_id !== auth()->user()->company_id, 403);

        $latest        = $user->salaries()->latest('effective_date')->first();
        $statusOptions = TaxPtkp::forSelect();

        return view('hris.salary.create', compact('user', 'latest', 'statusOptions'));
    }

    public function store(Request $request, User $user)
    {
        $this->authorize('create payroll');
        abort_if($user->company_id !== auth()->user()->company_id, 403);

        $request->validate([
            'gaji_pokok'           => 'required|numeric|min:0',
            'tunjangan_transport'  => 'nullable|numeric|min:0',
            'tunjangan_makan'      => 'nullable|numeric|min:0',
            'tunjangan_jabatan'    => 'nullable|numeric|min:0',
            'status_pajak'         => 'required|string',
            'npwp'                 => 'nullable|string|max:20',
            'bpjs_kesehatan'       => 'boolean',
            'bpjs_ketenagakerjaan' => 'boolean',
            'effective_date'       => 'required|date',
        ]);

        EmployeeSalary::create([
            'user_id'              => $user->id,
            'gaji_pokok'           => $request->gaji_pokok,
            'tunjangan_transport'  => $request->tunjangan_transport ?? 0,
            'tunjangan_makan'      => $request->tunjangan_makan ?? 0,
            'tunjangan_jabatan'    => $request->tunjangan_jabatan ?? 0,
            'npwp'                 => $request->npwp,
            'status_pajak'         => $request->status_pajak,
            'bpjs_kesehatan'       => $request->boolean('bpjs_kesehatan', true),
            'bpjs_ketenagakerjaan' => $request->boolean('bpjs_ketenagakerjaan', true),
            'effective_date'       => $request->effective_date,
        ]);

        return redirect()->route('hris.salary.index', $user)->with('success', 'Data gaji berhasil disimpan.');
    }

    public function edit(User $user, EmployeeSalary $salary)
    {
        $this->authorize('update payroll');
        abort_if($user->company_id !== auth()->user()->company_id || $salary->user_id !== $user->id, 403);

        $statusOptions = TaxPtkp::forSelect();

        return view('hris.salary.edit', compact('user', 'salary', 'statusOptions'));
    }

    public function update(Request $request, User $user, EmployeeSalary $salary)
    {
        $this->authorize('update payroll');
        abort_if($user->company_id !== auth()->user()->company_id || $salary->user_id !== $user->id, 403);

        $request->validate([
            'gaji_pokok'           => 'required|numeric|min:0',
            'tunjangan_transport'  => 'nullable|numeric|min:0',
            'tunjangan_makan'      => 'nullable|numeric|min:0',
            'tunjangan_jabatan'    => 'nullable|numeric|min:0',
            'status_pajak'         => 'required|string',
            'npwp'                 => 'nullable|string|max:20',
            'bpjs_kesehatan'       => 'boolean',
            'bpjs_ketenagakerjaan' => 'boolean',
            'effective_date'       => 'required|date',
        ]);

        $salary->update([
            'gaji_pokok'           => $request->gaji_pokok,
            'tunjangan_transport'  => $request->tunjangan_transport ?? 0,
            'tunjangan_makan'      => $request->tunjangan_makan ?? 0,
            'tunjangan_jabatan'    => $request->tunjangan_jabatan ?? 0,
            'npwp'                 => $request->npwp,
            'status_pajak'         => $request->status_pajak,
            'bpjs_kesehatan'       => $request->boolean('bpjs_kesehatan', true),
            'bpjs_ketenagakerjaan' => $request->boolean('bpjs_ketenagakerjaan', true),
            'effective_date'       => $request->effective_date,
        ]);

        return redirect()->route('hris.salary.index', $user)->with('success', 'Data gaji diperbarui.');
    }

    public function destroy(User $user, EmployeeSalary $salary)
    {
        $this->authorize('delete payroll');
        abort_if($salary->user_id !== $user->id, 403);
        $salary->delete();
        return back()->with('success', 'Data gaji dihapus.');
    }
}
