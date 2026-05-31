<?php

namespace App\Services;

use App\Models\EmployeeSalary;
use App\Models\TaxBracket;
use App\Models\TaxPtkp;

class PPh21Service
{
    public function hitungTahunan(
        float  $brutoSetahun,
        string $statusPajak,
        bool   $punyaNpwp = true
    ): array {
        $biayaJabatan = min($brutoSetahun * 0.05, 6_000_000);
        $ptkp         = TaxPtkp::getAmount($statusPajak);
        $pkp          = max(0, $brutoSetahun - $biayaJabatan - $ptkp);
        $pkp          = floor($pkp / 1000) * 1000;

        $brackets  = TaxBracket::getActive();
        $pajak     = 0.0;
        $breakdown = [];

        foreach ($brackets as $bracket) {
            if ($pkp <= $bracket->income_from) break;

            $batasAtas = $bracket->income_to ?? PHP_FLOAT_MAX;
            $kena      = min($pkp, $batasAtas) - $bracket->income_from;
            $amount    = $kena * $bracket->rate;
            $pajak    += $amount;

            $breakdown[] = [
                'label'  => $bracket->label,
                'pkp'    => $kena,
                'rate'   => $bracket->rate,
                'amount' => round($amount),
            ];
        }

        if (!$punyaNpwp) {
            $pajak *= 1.20;
        }

        return [
            'bruto_setahun'  => $brutoSetahun,
            'biaya_jabatan'  => $biayaJabatan,
            'ptkp'           => $ptkp,
            'pkp'            => $pkp,
            'pajak_setahun'  => round($pajak),
            'non_npwp_extra' => !$punyaNpwp,
            'breakdown'      => $breakdown,
        ];
    }

    public function hitungBulanan(EmployeeSalary $salary): array
    {
        $brutoSebulan = $salary->gaji_pokok + $salary->tunjangan_jabatan
                      + $salary->tunjangan_transport + $salary->tunjangan_makan;
        $brutoSetahun = $brutoSebulan * 12;
        $result       = $this->hitungTahunan(
            $brutoSetahun,
            $salary->status_pajak,
            (bool) $salary->npwp
        );

        return [
            ...$result,
            'pajak_bulanan' => round($result['pajak_setahun'] / 12),
        ];
    }
}
