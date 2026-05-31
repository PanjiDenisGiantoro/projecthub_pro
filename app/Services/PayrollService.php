<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Models\User;

class PayrollService
{
    public function generate(User $user, int $year, int $month): Payroll
    {
        $salary = $user->salaries()->latest('effective_date')->first();

        if (!$salary) {
            throw new \RuntimeException("Karyawan {$user->name} belum memiliki data gaji. Silakan atur data gaji terlebih dahulu.");
        }

        $lembur    = $this->totalLembur($user, $year, $month);
        $reimburse = $this->totalReimburse($user, $year, $month);
        $pph21     = (new PPh21Service)->hitungBulanan($salary);
        $bpjsKes   = $salary->bpjs_kesehatan
            ? round(min($salary->gaji_pokok, 12_000_000) * 0.01) : 0;
        $bpjsTk    = $salary->bpjs_ketenagakerjaan
            ? round($salary->gaji_pokok * 0.02 + min($salary->gaji_pokok, 9_559_600) * 0.01) : 0;
        $totalPotongan = $pph21['pajak_bulanan'] + $bpjsKes + $bpjsTk;
        $bruto = $salary->gaji_pokok + $salary->tunjangan_transport
               + $salary->tunjangan_makan + $salary->tunjangan_jabatan
               + $lembur + $reimburse;

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
