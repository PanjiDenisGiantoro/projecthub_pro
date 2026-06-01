<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use App\Models\Payroll;
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

        // --- Kehadiran ---
        $hariKerja  = $this->hitungHariKerja($year, $month);
        $hariHadir  = $this->totalHadir($user, $year, $month);
        $hariCuti   = $this->totalCutiDibayar($user, $year, $month);
        $hariAlpha  = max(0, $hariKerja - $hariHadir - $hariCuti);
        $potonganAlpha = $hariKerja > 0
            ? round(($hariAlpha / $hariKerja) * $salary->gaji_pokok)
            : 0;

        // --- Pendapatan ---
        $lembur    = $this->totalLembur($user, $year, $month);
        $reimburse = $this->totalReimburse($user, $year, $month);
        $bruto = $salary->gaji_pokok + $salary->tunjangan_transport
               + $salary->tunjangan_makan + $salary->tunjangan_jabatan
               + $lembur + $reimburse;

        // --- Potongan ---
        $pph21   = (new PPh21Service)->hitungBulanan($salary);
        $bpjsKes = $salary->bpjs_kesehatan
            ? round(min($salary->gaji_pokok, 12_000_000) * 0.01) : 0;
        $bpjsTk  = $salary->bpjs_ketenagakerjaan
            ? round($salary->gaji_pokok * 0.02 + min($salary->gaji_pokok, 9_559_600) * 0.01) : 0;
        $totalPotongan = $pph21['pajak_bulanan'] + $bpjsKes + $bpjsTk + $potonganAlpha;

        return Payroll::updateOrCreate(
            ['user_id' => $user->id, 'year' => $year, 'month' => $month],
            [
                'company_id'          => $user->company_id,
                'gaji_pokok'          => $salary->gaji_pokok,
                'tunjangan_transport' => $salary->tunjangan_transport,
                'tunjangan_makan'     => $salary->tunjangan_makan,
                'tunjangan_jabatan'   => $salary->tunjangan_jabatan,
                'tunjangan_lainnya'   => 0,
                'lembur'              => $lembur,
                'reimburse'           => $reimburse,
                'hari_kerja'          => $hariKerja,
                'hari_hadir'          => $hariHadir,
                'hari_cuti'           => $hariCuti,
                'hari_alpha'          => $hariAlpha,
                'potongan_alpha'      => $potonganAlpha,
                'penghasilan_bruto'   => $bruto,
                'potongan_bpjs_kes'   => $bpjsKes,
                'potongan_bpjs_tk'    => $bpjsTk,
                'potongan_pph21'      => $pph21['pajak_bulanan'],
                'total_potongan'      => $totalPotongan,
                'gaji_bersih'         => $bruto - $totalPotongan,
                'status'              => 'draft',
            ]
        );
    }

    private function hitungHariKerja(int $year, int $month): int
    {
        $day   = Carbon::create($year, $month, 1);
        $end   = $day->copy()->endOfMonth();
        $count = 0;
        while ($day->lte($end)) {
            if ($day->isWeekday()) $count++;
            $day->addDay();
        }
        return $count;
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
}
