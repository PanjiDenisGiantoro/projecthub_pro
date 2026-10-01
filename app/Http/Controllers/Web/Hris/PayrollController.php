<?php

namespace App\Http\Controllers\Web\Hris;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    use HasPerPage;

    public function __construct(private PayrollService $payrollService, private NotificationService $notifier) {}

    public function index(Request $request)
    {
        $user  = auth()->user();
        $year  = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $payrolls = Payroll::with('user')
            ->where('company_id', $user->company_id)
            ->when(!$user->can('view payroll'), fn($q) => $q->where('user_id', $user->id))
            ->where('year', $year)
            ->where('month', $month)
            ->orderBy('created_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $employees = $user->can('view payroll')
            ? User::where('company_id', $user->company_id)
                ->where('is_active', true)
                ->whereDoesntHave('roles', fn($q) => $q->where('name', 'client'))
                ->withExists('salaries as has_salary')
                ->orderBy('name')
                ->get()
            : collect();

        // Status payroll yang sudah ada di periode ini, untuk ditandai di daftar generate.
        $existingStatus = $user->can('generate payroll')
            ? Payroll::where('company_id', $user->company_id)
                ->where('year', $year)->where('month', $month)
                ->pluck('status', 'user_id')
            : collect();

        // Ringkasan status satu periode (bukan per halaman) untuk alur Generate → Finalize.
        $statusCounts = $user->can('generate payroll') || $user->can('update payroll')
            ? Payroll::where('company_id', $user->company_id)
                ->where('year', $year)->where('month', $month)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
            : collect();

        return view('hris.payroll.index', compact('payrolls', 'year', 'month', 'employees', 'existingStatus', 'statusCounts'));
    }

    public function generate(Request $request)
    {
        $this->authorize('generate payroll');
        $request->validate([
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'year'       => 'required|integer|min:2020',
            'month'      => 'required|integer|min:1|max:12',
        ]);

        $companyId = auth()->user()->company_id;
        $year      = (int) $request->year;
        $month     = (int) $request->month;

        $employees = User::whereIn('id', $request->user_ids)
            ->where('company_id', $companyId)
            ->get();
        abort_if($employees->count() !== count(array_unique($request->user_ids)), 403);

        // Payroll yang sudah finalized/paid tidak boleh tertimpa ulang jadi draft.
        $locked = Payroll::where('company_id', $companyId)
            ->where('year', $year)->where('month', $month)
            ->whereIn('user_id', $employees->pluck('id'))
            ->whereIn('status', ['finalized', 'paid'])
            ->pluck('user_id')
            ->all();

        $ok = [];
        $skipped = [];
        $failed = [];

        foreach ($employees as $employee) {
            if (in_array($employee->id, $locked)) {
                $skipped[] = $employee->name;
                continue;
            }

            try {
                $payroll = $this->payrollService->generate($employee, $year, $month);

                $this->notifier->send(
                    $employee->id,
                    'payroll_generated',
                    'Payroll Baru Dibuat',
                    "Payroll Anda untuk periode {$month}/{$year} telah dibuat.",
                    ['payroll_id' => $payroll->id]
                );

                $ok[] = $employee->name;
            } catch (\Exception $e) {
                $failed[] = $e->getMessage();
            }
        }

        $redirect = back();
        if ($ok) {
            $redirect = $redirect->with('success', count($ok) === 1
                ? "Payroll berhasil digenerate untuk {$ok[0]}."
                : 'Payroll berhasil digenerate untuk ' . count($ok) . ' karyawan.');
        }
        $errors = $failed;
        if ($skipped) {
            $errors[] = 'Dilewati karena sudah final/dibayar: ' . implode(', ', $skipped) . '.';
        }
        if ($errors) {
            $redirect = $redirect->with('error', implode(' ', $errors));
        }

        return $redirect;
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
        $this->authorize('update payroll');
        abort_if($payroll->company_id !== auth()->user()->company_id, 403);
        abort_if($payroll->status !== 'draft', 422, 'Hanya draft yang bisa difinalize.');

        $this->markFinalized($payroll);

        return back()->with('success', 'Payroll difinalize.');
    }

    public function finalizeBulk(Request $request)
    {
        $this->authorize('update payroll');
        $request->validate([
            'payroll_ids'   => 'required_without:all|array|min:1',
            'payroll_ids.*' => 'integer',
            'all'           => 'nullable|boolean',
            'year'          => 'required_with:all|integer|min:2020',
            'month'         => 'required_with:all|integer|min:1|max:12',
        ]);

        $payrolls = Payroll::where('company_id', auth()->user()->company_id)
            ->where('status', 'draft')
            ->when(
                $request->boolean('all'),
                fn($q) => $q->where('year', $request->year)->where('month', $request->month),
                fn($q) => $q->whereIn('id', $request->payroll_ids)
            )
            ->get();

        if ($payrolls->isEmpty()) {
            return back()->with('error', 'Tidak ada payroll draft yang bisa difinalize.');
        }

        foreach ($payrolls as $payroll) {
            $this->markFinalized($payroll);
        }

        return back()->with('success', "{$payrolls->count()} payroll berhasil difinalize.");
    }

    private function markFinalized(Payroll $payroll): void
    {
        $payroll->update(['status' => 'finalized']);

        $this->notifier->send(
            $payroll->user_id,
            'payroll_finalized',
            'Slip Gaji Siap',
            "Payroll Anda untuk periode {$payroll->month}/{$payroll->year} sudah final dan slip gaji siap diunduh.",
            ['payroll_id' => $payroll->id]
        );
    }
}
