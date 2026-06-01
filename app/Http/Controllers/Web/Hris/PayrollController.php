<?php

namespace App\Http\Controllers\Web\Hris;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\User;
use App\Services\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payrollService) {}

    public function index(Request $request)
    {
        $user  = auth()->user();
        $year  = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $payrolls = Payroll::with('user')
            ->where('company_id', $user->company_id)
            ->when(!$user->can('manage payroll'), fn($q) => $q->where('user_id', $user->id))
            ->where('year', $year)
            ->where('month', $month)
            ->orderBy('created_at')
            ->paginate(30);

        $employees = $user->can('manage payroll')
            ? User::where('company_id', $user->company_id)->where('is_active', true)->get()
            : collect();

        return view('hris.payroll.index', compact('payrolls', 'year', 'month', 'employees'));
    }

    public function generate(Request $request)
    {
        $this->authorize('generate payroll');
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'year'    => 'required|integer|min:2020',
            'month'   => 'required|integer|min:1|max:12',
        ]);

        $employee = User::findOrFail($request->user_id);
        abort_if($employee->company_id !== auth()->user()->company_id, 403);

        try {
            $this->payrollService->generate($employee, $request->year, $request->month);
            return back()->with('success', "Payroll berhasil digenerate untuk {$employee->name}.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(Payroll $payroll)
    {
        abort_if($payroll->company_id !== auth()->user()->company_id, 403);
        $payroll->load('user');
        return view('hris.payroll.show', compact('payroll'));
    }

    public function cetakSlip(Payroll $payroll)
    {
        abort_if($payroll->company_id !== auth()->user()->company_id, 403);
        $payroll->load('user.company');
        $pdf = Pdf::loadView('hris.payroll.slip', compact('payroll'))->setPaper('a4');
        return $pdf->download("slip-gaji-{$payroll->user->name}-{$payroll->year}-{$payroll->month}.pdf");
    }

    public function finalize(Payroll $payroll)
    {
        $this->authorize('manage payroll');
        abort_if($payroll->status !== 'draft', 422, 'Hanya draft yang bisa difinalize.');
        $payroll->update(['status' => 'finalized']);
        return back()->with('success', 'Payroll difinalize.');
    }
}
