<?php

namespace App\Http\Controllers\Web\Hris;

use App\Http\Controllers\Controller;
use App\Models\Bonus;
use App\Models\User;
use Illuminate\Http\Request;

class BonusController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view payroll');
        $companyId = auth()->user()->company_id;

        $year  = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $bonuses = Bonus::with('user')
            ->where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->orderByDesc('created_at')
            ->get();

        $employees = User::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();

        return view('hris.bonus.index', compact('bonuses', 'employees', 'year', 'month'));
    }

    public function store(Request $request)
    {
        $this->authorize('create payroll');
        $companyId = auth()->user()->company_id;

        $request->validate([
            'user_id'     => 'required|exists:users,id',
            'year'        => 'required|integer|min:2020',
            'month'       => 'required|integer|min:1|max:12',
            'type'        => 'required|in:bonus,thr,gratifikasi,jasa_produksi,lainnya',
            'amount'      => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $employee = User::findOrFail($request->user_id);
        abort_if($employee->company_id !== $companyId, 403);

        Bonus::create([
            'user_id'     => $employee->id,
            'company_id'  => $companyId,
            'year'        => $request->year,
            'month'       => $request->month,
            'type'        => $request->type,
            'amount'      => $request->amount,
            'description' => $request->description,
            'created_by'  => auth()->id(),
        ]);

        return back()->with('success', 'Bonus/THR berhasil ditambahkan.');
    }

    public function destroy(Bonus $bonus)
    {
        $this->authorize('delete payroll');
        abort_if($bonus->company_id !== auth()->user()->company_id, 403);

        $bonus->delete();

        return back()->with('success', 'Data bonus dihapus.');
    }
}
