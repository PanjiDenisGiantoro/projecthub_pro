<?php

namespace App\Http\Controllers\Api\Hris;

use App\Http\Controllers\Controller;
use App\Models\Bonus;
use App\Models\EmployeeSalary;
use App\Models\Kasbon;
use App\Models\Payroll;
use App\Models\TaxPtkp;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Payroll, bonus, kasbon, dan data gaji untuk aplikasi mobile. Izin sama
 * dengan controller web di Web\Hris; karyawan tanpa izin "view payroll"
 * hanya melihat slip & kasbon miliknya sendiri.
 */
class PayrollController extends Controller
{
    public function __construct(private PayrollService $payrollService, private NotificationService $notifier) {}

    // ── Payroll ──────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $payrolls = Payroll::with('user:id,name')
            ->where('company_id', $user->company_id)
            ->when(!$user->can('view payroll'), fn ($q) => $q->where('user_id', $user->id))
            ->when($request->year, fn ($q) => $q->where('year', $request->integer('year')))
            ->when($request->month, fn ($q) => $q->where('month', $request->integer('month')))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(200)
            ->get();

        return response()->json($payrolls->map(fn ($p) => $this->payrollJson($p))->values());
    }

    public function show(Request $request, Payroll $payroll): JsonResponse
    {
        $this->authorizePayrollView($request->user(), $payroll);

        return response()->json($this->payrollJson($payroll->load('user:id,name')));
    }

    public function generate(Request $request): JsonResponse
    {
        $this->authorize('generate payroll');
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'year'    => 'required|integer|min:2020',
            'month'   => 'required|integer|min:1|max:12',
        ]);

        $employee = User::findOrFail($request->user_id);
        abort_if($employee->company_id !== $request->user()->company_id, 403);

        try {
            $payroll = $this->payrollService->generate($employee, $request->integer('year'), $request->integer('month'));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $this->notifier->send(
            $employee->id,
            'payroll_generated',
            'Payroll Baru Dibuat',
            "Payroll Anda untuk periode {$request->month}/{$request->year} telah dibuat.",
            ['payroll_id' => $payroll->id]
        );

        return response()->json($this->payrollJson($payroll->load('user:id,name')), 201);
    }

    public function finalize(Request $request, Payroll $payroll): JsonResponse
    {
        $this->authorize('update payroll');
        abort_if($payroll->company_id !== $request->user()->company_id, 403);
        abort_if($payroll->status !== 'draft', 422, 'Hanya draft yang bisa difinalize.');
        $payroll->update(['status' => 'finalized']);

        $this->notifier->send(
            $payroll->user_id,
            'payroll_finalized',
            'Slip Gaji Siap',
            "Payroll Anda untuk periode {$payroll->month}/{$payroll->year} sudah final dan slip gaji siap diunduh.",
            ['payroll_id' => $payroll->id]
        );

        return response()->json($this->payrollJson($payroll->fresh()->load('user:id,name')));
    }

    private function authorizePayrollView(User $user, Payroll $payroll): void
    {
        abort_if($payroll->company_id !== $user->company_id, 403);
        abort_unless($payroll->user_id === $user->id || $user->can('view payroll'), 403);
    }

    private function payrollJson(Payroll $p): array
    {
        $num = fn ($v) => $v === null ? 0.0 : (float) $v;

        return [
            'id'           => $p->id,
            'user_id'      => $p->user_id,
            'user_name'    => $p->user?->name,
            'year'         => (int) $p->year,
            'month'        => (int) $p->month,
            'status'       => $p->status,
            'paid_at'      => $p->paid_at?->toIso8601String(),
            'pph21_method' => $p->pph21_method,
            'pph21_scheme' => $p->pph21_scheme,
            'hari'         => [
                'kerja' => (int) $p->hari_kerja,
                'hadir' => (int) $p->hari_hadir,
                'cuti'  => (int) $p->hari_cuti,
                'alpha' => (int) $p->hari_alpha,
            ],
            'pendapatan' => [
                'gaji_pokok'          => $num($p->gaji_pokok),
                'tunjangan_transport' => $num($p->tunjangan_transport),
                'tunjangan_makan'     => $num($p->tunjangan_makan),
                'tunjangan_jabatan'   => $num($p->tunjangan_jabatan),
                'tunjangan_lainnya'   => $num($p->tunjangan_lainnya),
                'tunjangan_pph21'     => $num($p->tunjangan_pph21),
                'bonus'               => $num($p->bonus),
                'lembur'              => $num($p->lembur),
                'reimburse'           => $num($p->reimburse),
            ],
            'potongan' => [
                'alpha'     => $num($p->potongan_alpha),
                'kasbon'    => $num($p->potongan_kasbon),
                'bpjs_kes'  => $num($p->potongan_bpjs_kes),
                'bpjs_tk'   => $num($p->potongan_bpjs_tk),
                'pph21'     => $num($p->potongan_pph21),
                'lainnya'   => $num($p->potongan_lainnya),
            ],
            'tanggungan' => [
                'bpjs_kes' => $num($p->tanggungan_bpjs_kes),
                'bpjs_tk'  => $num($p->tanggungan_bpjs_tk),
                'pph21'    => $num($p->tanggungan_pph21),
            ],
            'penghasilan_bruto'           => $num($p->penghasilan_bruto),
            'total_potongan'              => $num($p->total_potongan),
            'total_tanggungan_perusahaan' => $num($p->total_tanggungan_perusahaan),
            'gaji_bersih'                 => $num($p->gaji_bersih),
        ];
    }

    // ── Bonus / THR ──────────────────────────────────────────────────────────

    public function bonuses(Request $request): JsonResponse
    {
        $user = $request->user();

        $bonuses = Bonus::with('user:id,name')
            ->where('company_id', $user->company_id)
            ->when(!$user->can('view payroll'), fn ($q) => $q->where('user_id', $user->id))
            ->when($request->year, fn ($q) => $q->where('year', $request->integer('year')))
            ->when($request->month, fn ($q) => $q->where('month', $request->integer('month')))
            ->orderByDesc('year')->orderByDesc('month')->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return response()->json($bonuses->map(fn ($b) => $this->bonusJson($b))->values());
    }

    public function storeBonus(Request $request): JsonResponse
    {
        $this->authorize('create payroll');
        $companyId = $request->user()->company_id;

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

        $bonus = Bonus::create([
            'user_id'     => $employee->id,
            'company_id'  => $companyId,
            'year'        => $request->year,
            'month'       => $request->month,
            'type'        => $request->type,
            'amount'      => $request->amount,
            'description' => $request->description,
            'created_by'  => $request->user()->id,
        ]);

        return response()->json($this->bonusJson($bonus->load('user:id,name')), 201);
    }

    public function destroyBonus(Request $request, Bonus $bonus): JsonResponse
    {
        $this->authorize('delete payroll');
        abort_if($bonus->company_id !== $request->user()->company_id, 403);
        $bonus->delete();

        return response()->json(['message' => 'Data bonus dihapus.']);
    }

    private function bonusJson(Bonus $b): array
    {
        return [
            'id'          => $b->id,
            'user_id'     => $b->user_id,
            'user_name'   => $b->user?->name,
            'year'        => (int) $b->year,
            'month'       => (int) $b->month,
            'type'        => $b->type,
            'amount'      => (float) $b->amount,
            'description' => $b->description,
        ];
    }

    // ── Kasbon ───────────────────────────────────────────────────────────────

    public function kasbons(Request $request): JsonResponse
    {
        $user = $request->user();

        $kasbons = Kasbon::with('user:id,name')
            ->where('company_id', $user->company_id)
            ->when(!$user->can('view payroll'), fn ($q) => $q->where('user_id', $user->id))
            ->orderByRaw("status = 'berjalan' desc")
            ->orderByDesc('tanggal')
            ->limit(200)
            ->get();

        return response()->json($kasbons->map(fn ($k) => $this->kasbonJson($k))->values());
    }

    public function storeKasbon(Request $request): JsonResponse
    {
        $this->authorize('create payroll');
        $companyId = $request->user()->company_id;

        $request->validate([
            'user_id'           => 'required|exists:users,id',
            'tanggal'           => 'required|date',
            'jumlah'            => 'required|numeric|min:1',
            'cicilan_per_bulan' => 'required|numeric|min:1',
            'keterangan'        => 'nullable|string|max:255',
        ]);

        $employee = User::findOrFail($request->user_id);
        abort_if($employee->company_id !== $companyId, 403);
        abort_if(
            Kasbon::where('user_id', $employee->id)->where('status', 'berjalan')->exists(),
            422,
            'Karyawan ini masih memiliki kasbon yang belum lunas.'
        );

        $kasbon = Kasbon::create([
            'user_id'           => $employee->id,
            'company_id'        => $companyId,
            'tanggal'           => $request->tanggal,
            'jumlah'            => $request->jumlah,
            'cicilan_per_bulan' => $request->cicilan_per_bulan,
            'sisa'              => $request->jumlah,
            'status'            => 'berjalan',
            'keterangan'        => $request->keterangan,
            'created_by'        => $request->user()->id,
        ]);

        return response()->json($this->kasbonJson($kasbon->load('user:id,name')), 201);
    }

    public function destroyKasbon(Request $request, Kasbon $kasbon): JsonResponse
    {
        $this->authorize('delete payroll');
        abort_if($kasbon->company_id !== $request->user()->company_id, 403);
        abort_if($kasbon->sisa != $kasbon->jumlah, 422, 'Kasbon yang sudah dipotong dari payroll tidak bisa dihapus.');
        $kasbon->delete();

        return response()->json(['message' => 'Data kasbon dihapus.']);
    }

    private function kasbonJson(Kasbon $k): array
    {
        return [
            'id'                        => $k->id,
            'user_id'                   => $k->user_id,
            'user_name'                 => $k->user?->name,
            'tanggal'                   => $k->tanggal?->toDateString(),
            'jumlah'                    => (float) $k->jumlah,
            'cicilan_per_bulan'         => (float) $k->cicilan_per_bulan,
            'sisa'                      => (float) $k->sisa,
            'periode_terakhir'          => $k->periode_terakhir,
            'potongan_periode_terakhir' => $k->potongan_periode_terakhir !== null ? (float) $k->potongan_periode_terakhir : null,
            'status'                    => $k->status,
            'keterangan'                => $k->keterangan,
        ];
    }

    // ── Gaji karyawan ────────────────────────────────────────────────────────

    public function salaries(Request $request, User $user): JsonResponse
    {
        abort_if($user->company_id !== $request->user()->company_id, 403);
        abort_unless($user->id === $request->user()->id || $request->user()->can('view payroll'), 403);

        return response()->json([
            'status_options' => TaxPtkp::forSelect(),
            'salaries'       => $user->salaries()->orderByDesc('effective_date')->get()->map(fn ($s) => $this->salaryJson($s))->values(),
        ]);
    }

    public function storeSalary(Request $request, User $user): JsonResponse
    {
        $this->authorize('create payroll');
        abort_if($user->company_id !== $request->user()->company_id, 403);
        $request->validate($this->salaryRules());

        $salary = EmployeeSalary::create(['user_id' => $user->id, ...$this->salaryData($request)]);

        return response()->json($this->salaryJson($salary), 201);
    }

    public function updateSalary(Request $request, User $user, EmployeeSalary $salary): JsonResponse
    {
        $this->authorize('update payroll');
        abort_if($user->company_id !== $request->user()->company_id || $salary->user_id !== $user->id, 403);
        $request->validate($this->salaryRules());

        $salary->update($this->salaryData($request));

        return response()->json($this->salaryJson($salary->fresh()));
    }

    private function salaryRules(): array
    {
        return [
            'gaji_pokok'           => 'required|numeric|min:0',
            'tunjangan_transport'  => 'nullable|numeric|min:0',
            'tunjangan_makan'      => 'nullable|numeric|min:0',
            'tunjangan_jabatan'    => 'nullable|numeric|min:0',
            'status_pajak'         => 'required|string',
            'npwp'                 => 'nullable|string|max:20',
            'bpjs_kesehatan'       => 'boolean',
            'bpjs_ketenagakerjaan' => 'boolean',
            'effective_date'       => 'required|date',
        ];
    }

    private function salaryData(Request $request): array
    {
        return [
            'gaji_pokok'           => $request->gaji_pokok,
            'tunjangan_transport'  => $request->tunjangan_transport ?? 0,
            'tunjangan_makan'      => $request->tunjangan_makan ?? 0,
            'tunjangan_jabatan'    => $request->tunjangan_jabatan ?? 0,
            'npwp'                 => $request->npwp,
            'status_pajak'         => $request->status_pajak,
            'bpjs_kesehatan'       => $request->boolean('bpjs_kesehatan'),
            'bpjs_ketenagakerjaan' => $request->boolean('bpjs_ketenagakerjaan'),
            'effective_date'       => $request->effective_date,
        ];
    }

    private function salaryJson(EmployeeSalary $s): array
    {
        return [
            'id'                   => $s->id,
            'user_id'              => $s->user_id,
            'gaji_pokok'           => (float) $s->gaji_pokok,
            'tunjangan_transport'  => (float) $s->tunjangan_transport,
            'tunjangan_makan'      => (float) $s->tunjangan_makan,
            'tunjangan_jabatan'    => (float) $s->tunjangan_jabatan,
            'npwp'                 => $s->npwp,
            'status_pajak'         => $s->status_pajak,
            'bpjs_kesehatan'       => $s->bpjs_kesehatan,
            'bpjs_ketenagakerjaan' => $s->bpjs_ketenagakerjaan,
            'effective_date'       => $s->effective_date?->toDateString(),
        ];
    }
}
