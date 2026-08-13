<?php

namespace App\Http\Controllers\Web\Hris;

use App\Http\Controllers\Controller;
use App\Models\Kasbon;
use App\Models\User;
use Illuminate\Http\Request;

class KasbonController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view payroll');
        $companyId = auth()->user()->company_id;

        $kasbons = Kasbon::with('user')
            ->where('company_id', $companyId)
            ->orderByRaw("status = 'berjalan' desc")
            ->orderByDesc('tanggal')
            ->get();

        $employees = User::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();

        return view('hris.kasbon.index', compact('kasbons', 'employees'));
    }

    public function store(Request $request)
    {
        $this->authorize('create payroll');
        $companyId = auth()->user()->company_id;

        $request->validate([
            'user_id'           => 'required|exists:users,id',
            'tanggal'           => 'required|date',
            'jumlah'            => 'required|numeric|min:1',
            'cicilan_per_bulan' => 'required|numeric|min:1',
            'keterangan'        => 'nullable|string|max:255',
        ]);

        $employee = User::findOrFail($request->user_id);
        abort_if($employee->company_id !== $companyId, 403);

        $sudahBerjalan = Kasbon::where('user_id', $employee->id)
            ->where('status', 'berjalan')
            ->exists();
        abort_if($sudahBerjalan, 422, 'Karyawan ini masih memiliki kasbon yang belum lunas.');

        Kasbon::create([
            'user_id'           => $employee->id,
            'company_id'        => $companyId,
            'tanggal'           => $request->tanggal,
            'jumlah'            => $request->jumlah,
            'cicilan_per_bulan' => $request->cicilan_per_bulan,
            'sisa'              => $request->jumlah,
            'status'            => 'berjalan',
            'keterangan'        => $request->keterangan,
            'created_by'        => auth()->id(),
        ]);

        return back()->with('success', 'Kasbon berhasil ditambahkan.');
    }

    public function destroy(Kasbon $kasbon)
    {
        $this->authorize('delete payroll');
        abort_if($kasbon->company_id !== auth()->user()->company_id, 403);
        abort_if($kasbon->sisa != $kasbon->jumlah, 422, 'Kasbon yang sudah dipotong dari payroll tidak bisa dihapus.');

        $kasbon->delete();

        return back()->with('success', 'Data kasbon dihapus.');
    }
}
