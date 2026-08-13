<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Bonus;
use App\Models\Kasbon;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Pph21Setting;
use App\Models\Reimbursement;
use App\Models\User;
use Carbon\Carbon;

class PayrollService
{
    public function generate(User $user, int $year, int $month): Payroll
    {
        $salary = $user->salaries()->latest('effective_date')->first();

        if (!$salary) {
            throw new \RuntimeException("Karyawan {$user->name} belum memiliki data gaji. Silakan atur data gaji terlebih dahulu.");
        }

        $setting = Pph21Setting::forCompany($user->company_id);

        // --- Kehadiran ---
        $hariKerja  = $this->hitungHariKerja($year, $month);
        $hariHadir  = $this->totalHadir($user, $year, $month);
        $hariCuti   = $this->totalCutiDibayar($user, $year, $month);
        $hariAlpha  = max(0, $hariKerja - $hariHadir - $hariCuti);
        $potonganAlpha = $this->hitungPotonganAlpha($setting, $salary, $hariKerja, $hariAlpha);

        $lembur    = $this->totalLembur($user, $year, $month);
        $reimburse = $this->totalReimburse($user, $year, $month);
        $bonus     = $this->totalBonus($user, $year, $month);
        $potonganKasbon = $this->potonganKasbon($user, $year, $month);

        // --- PPh 21 ---
        $pph21     = $this->hitungPph21($user, $salary, $setting, $year, $month, $bonus);
        $tunjanganPph21 = $pph21['tunjangan_pph21'];
        $potonganPph21  = $setting->payment_scheme === 'net' ? 0 : $pph21['pajak_bulanan'];
        $tanggunganPph21 = $setting->payment_scheme === 'net' ? $pph21['pajak_bulanan'] : 0;

        // --- Pendapatan ---
        // Tunjangan PPh21 (skema gross_up) ikut jadi pendapatan karyawan (& kena pajak lagi, sudah diperhitungkan saat iterasi).
        $bruto = $salary->gaji_pokok + $salary->tunjangan_transport
               + $salary->tunjangan_makan + $salary->tunjangan_jabatan
               + $lembur + $reimburse + $bonus + $tunjanganPph21;

        // --- Potongan ---
        $bpjsKes = $salary->bpjs_kesehatan
            ? round(min($salary->gaji_pokok, 12_000_000) * 0.01) : 0;
        $bpjsTk  = $salary->bpjs_ketenagakerjaan
            ? round($salary->gaji_pokok * 0.02 + min($salary->gaji_pokok, 9_559_600) * 0.01) : 0;
        $totalPotongan = $potonganPph21 + $bpjsKes + $bpjsTk + $potonganAlpha + $potonganKasbon;

        // --- Tanggungan Perusahaan (tidak mengurangi gaji bersih karyawan) ---
        $tanggunganBpjsKes = $salary->bpjs_kesehatan
            ? round(min($salary->gaji_pokok, 12_000_000) * 0.04) : 0;
        $tanggunganBpjsTk = $salary->bpjs_ketenagakerjaan
            ? round(
                $salary->gaji_pokok * 0.037                              // JHT employer
                + min($salary->gaji_pokok, 9_559_600) * 0.02             // JP employer
                + $salary->gaji_pokok * $setting->jkk_rate               // JKK employer
                + $salary->gaji_pokok * 0.003                            // JKM employer
            ) : 0;
        $totalTanggungan = $tanggunganBpjsKes + $tanggunganBpjsTk + $tanggunganPph21;

        return Payroll::updateOrCreate(
            ['user_id' => $user->id, 'year' => $year, 'month' => $month],
            [
                'company_id'          => $user->company_id,
                'gaji_pokok'          => $salary->gaji_pokok,
                'tunjangan_transport' => $salary->tunjangan_transport,
                'tunjangan_makan'     => $salary->tunjangan_makan,
                'tunjangan_jabatan'   => $salary->tunjangan_jabatan,
                'tunjangan_lainnya'   => 0,
                'bonus'               => $bonus,
                'lembur'              => $lembur,
                'reimburse'           => $reimburse,
                'hari_kerja'          => $hariKerja,
                'hari_hadir'          => $hariHadir,
                'hari_cuti'           => $hariCuti,
                'hari_alpha'          => $hariAlpha,
                'potongan_alpha'      => $potonganAlpha,
                'potongan_kasbon'     => $potonganKasbon,
                'penghasilan_bruto'   => $bruto,
                'potongan_bpjs_kes'   => $bpjsKes,
                'potongan_bpjs_tk'    => $bpjsTk,
                'potongan_pph21'      => $potonganPph21,
                'tunjangan_pph21'     => $tunjanganPph21,
                'pph21_method'        => $pph21['metode'],
                'pph21_scheme'        => $setting->payment_scheme,
                'total_potongan'      => $totalPotongan,
                'tanggungan_bpjs_kes' => $tanggunganBpjsKes,
                'tanggungan_bpjs_tk'  => $tanggunganBpjsTk,
                'tanggungan_pph21'    => $tanggunganPph21,
                'total_tanggungan_perusahaan' => $totalTanggungan,
                'gaji_bersih'         => $bruto - $totalPotongan,
                'status'              => 'draft',
            ]
        );
    }

    /**
     * Hitung potongan PPh 21 sesuai metode yang dipilih perusahaan (progresif/TER) dan skema
     * pembayaran (gross/gross_up/net). Untuk gross_up, tunjangan pajak dicari lewat iterasi
     * titik tetap di PPh21Service::hitungGrossUp() (bulanan) — untuk Desember TER, iterasi
     * dilakukan di sini karena butuh rekonsiliasi bruto riil Jan-Nov terlebih dahulu.
     */
    private function hitungPph21(User $user, $salary, Pph21Setting $setting, int $year, int $month, float $bonusBulanan = 0): array
    {
        $service = new PPh21Service();
        $grossUp = $setting->payment_scheme === 'gross_up';

        if ($setting->method !== 'ter') {
            $result = $grossUp
                ? $service->hitungGrossUp($salary, $setting, false, $bonusBulanan)
                : $service->hitungBulanan($salary, $setting, $bonusBulanan);
            return [
                'pajak_bulanan'   => $result['pajak_bulanan'],
                'metode'          => 'progresif',
                'tunjangan_pph21' => $result['tunjangan_pph21'] ?? 0,
            ];
        }

        if ($month < 12) {
            $result = $grossUp
                ? $service->hitungGrossUp($salary, $setting, true, $bonusBulanan)
                : $service->hitungBulananTer($salary, $setting, $bonusBulanan);
            return [
                'pajak_bulanan'   => $result['pajak_bulanan'],
                'metode'          => 'ter',
                'tunjangan_pph21' => $result['tunjangan_pph21'] ?? 0,
            ];
        }

        // Desember: rekonsiliasi — jumlahkan bruto & potongan riil Jan-Nov dari payroll yang sudah ada.
        $priorPayrolls = Payroll::where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', '<', 12)
            ->get();

        $brutoJanNov = $priorPayrolls->sum(fn ($p) => $setting->brutoPajak(
            $p->gaji_pokok, $p->tunjangan_jabatan, $p->tunjangan_transport, $p->tunjangan_makan
        ) + $p->bonus + $p->tunjangan_pph21);
        $pphJanNov = $priorPayrolls->sum('potongan_pph21');

        $brutoDesemberDasar = $setting->brutoPajak(
            $salary->gaji_pokok, $salary->tunjangan_jabatan,
            $salary->tunjangan_transport, $salary->tunjangan_makan
        ) + $bonusBulanan;

        if (!$grossUp) {
            $result = $service->hitungDesember(
                $brutoJanNov + $brutoDesemberDasar,
                $salary->status_pajak,
                (bool) $salary->npwp,
                $pphJanNov
            );
            return ['pajak_bulanan' => $result['pajak_bulanan'], 'metode' => 'ter', 'tunjangan_pph21' => 0];
        }

        // Gross-up Desember: iterasi tunjangan Desember sampai konvergen dengan pajak hasil
        // rekonsiliasi. Kalau hasilnya kelebihan potong (negatif), tunjangan = 0 — refund
        // tidak perlu di-gross-up.
        $tunjanganDesember = 0.0;
        $result = null;
        for ($i = 0; $i < 20; $i++) {
            $result = $service->hitungDesember(
                $brutoJanNov + $brutoDesemberDasar + $tunjanganDesember,
                $salary->status_pajak,
                (bool) $salary->npwp,
                $pphJanNov
            );
            $pajakBaru = max(0, $result['pajak_bulanan']);
            $konvergen = abs($pajakBaru - $tunjanganDesember) < 1;
            $tunjanganDesember = $pajakBaru;
            if ($konvergen) break;
        }

        return [
            'pajak_bulanan'   => $result['pajak_bulanan'],
            'metode'          => 'ter',
            'tunjangan_pph21' => $tunjanganDesember,
        ];
    }

    /**
     * Hitung hari kerja (Senin-Jumat) dalam periode. Untuk bulan yang masih berjalan,
     * dibatasi sampai kemarin — hari yang belum terlewati tidak boleh ikut dianggap
     * alpha karena karyawan belum sempat absen.
     */
    private function hitungHariKerja(int $year, int $month): int
    {
        $day = Carbon::create($year, $month, 1);
        $end = $day->copy()->endOfMonth();

        $kemarin = Carbon::yesterday();
        if ($end->gt($kemarin)) {
            $end = $kemarin->copy();
        }
        if ($day->gt($end)) {
            return 0;
        }

        $count = 0;
        while ($day->lte($end)) {
            if ($day->isWeekday()) $count++;
            $day->addDay();
        }
        return $count;
    }

    /**
     * Nominal potongan alpha sesuai pengaturan perusahaan: proporsional dari gaji pokok
     * (gaji pokok ÷ hari kerja × hari alpha) atau nominal tetap per hari alpha.
     */
    private function hitungPotonganAlpha(Pph21Setting $setting, $salary, int $hariKerja, int $hariAlpha): float
    {
        if (!$setting->potong_alpha || $hariAlpha <= 0) {
            return 0;
        }

        if ($setting->potongan_alpha_metode === 'nominal') {
            return $hariAlpha * $setting->potongan_alpha_nominal;
        }

        return $hariKerja > 0
            ? round(($hariAlpha / $hariKerja) * $salary->gaji_pokok)
            : 0;
    }

    private function totalHadir(User $user, int $year, int $month): int
    {
        return Attendance::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNotNull('check_in')
            ->count();
    }

    private function totalCutiDibayar(User $user, int $year, int $month): int
    {
        $firstDay = Carbon::create($year, $month, 1)->startOfDay();
        $lastDay  = $firstDay->copy()->endOfMonth();

        $requests = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereHas('leaveType', fn($q) => $q->where('is_paid', true))
            ->where('start_date', '<=', $lastDay)
            ->where('end_date', '>=', $firstDay)
            ->with('leaveType')
            ->get();

        $total = 0;
        foreach ($requests as $req) {
            $start = $req->start_date->lt($firstDay) ? $firstDay->copy() : $req->start_date->copy();
            $end   = $req->end_date->gt($lastDay)    ? $lastDay->copy()  : $req->end_date->copy();
            $cur   = $start->copy();
            while ($cur->lte($end)) {
                if ($cur->isWeekday()) $total++;
                $cur->addDay();
            }
        }
        return $total;
    }

    private function totalLembur(User $user, int $year, int $month): float
    {
        return Overtime::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('status', 'approved')
            ->sum('total_amount');
    }

    private function totalReimburse(User $user, int $year, int $month): float
    {
        return Reimbursement::where('user_id', $user->id)
            ->whereYear('expense_date', $year)
            ->whereMonth('expense_date', $month)
            ->where('status', 'approved')
            ->sum('amount');
    }

    private function totalBonus(User $user, int $year, int $month): float
    {
        return Bonus::where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->sum('amount');
    }

    /**
     * Potong cicilan kasbon karyawan yang masih berjalan untuk periode ini. Idempoten: kalau
     * payroll periode ini sudah pernah digenerate sebelumnya (periode_terakhir sudah tercatat),
     * pakai kembali nominal yang sama tanpa mengurangi sisa kasbon dua kali.
     */
    private function potonganKasbon(User $user, int $year, int $month): float
    {
        $kasbon = Kasbon::where('user_id', $user->id)
            ->where('status', 'berjalan')
            ->where('sisa', '>', 0)
            ->first();

        if (!$kasbon) {
            return 0;
        }

        $periode = "{$year}-{$month}";

        if ($kasbon->periode_terakhir === $periode) {
            return $kasbon->potongan_periode_terakhir;
        }

        $potongan = min($kasbon->cicilan_per_bulan, $kasbon->sisa);

        $kasbon->sisa -= $potongan;
        $kasbon->periode_terakhir = $periode;
        $kasbon->potongan_periode_terakhir = $potongan;
        if ($kasbon->sisa <= 0) {
            $kasbon->status = 'lunas';
        }
        $kasbon->save();

        return $potongan;
    }
}
